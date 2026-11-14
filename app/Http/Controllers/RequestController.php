<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Exceptions\WorkflowException;
use App\Models\RequestAttachment;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Services\AttachmentService;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RequestController extends Controller
{
    public function center(): View
    {
        $types = RequestType::query()
            ->where('is_active', true)
            ->with(['formTemplates' => fn ($query) => $query->where('is_active', true), 'workflowTemplates' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->filter(fn (RequestType $type) => $type->activeFormTemplate() !== null && $type->activeWorkflowTemplate() !== null)
            ->values();

        return view('requests.center', ['types' => $types]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:32'],
            'type' => ['nullable', 'string', 'max:64'],
        ]);

        $requests = ServiceRequest::query()
            ->where('requester_id', $request->user()->getKey())
            ->with('requestType', 'department', 'workflowInstance')
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $query->where(fn ($inner) => $inner
                    ->where('request_number', 'like', $like)
                    ->orWhere('title', 'like', $like));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->whereHas('requestType', fn ($inner) => $inner->where('code', $type)))
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $counts = ServiceRequest::query()
            ->where('requester_id', $request->user()->getKey())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('requests.index', [
            'requests' => $requests,
            'filters' => $filters,
            'counts' => $counts,
            'statuses' => RequestStatus::options(),
            'types' => RequestType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(ServiceRequest $request): View
    {
        Gate::authorize('view', $request);

        $request->load([
            'requestType',
            'formTemplate',
            'requester.department',
            'department',
            'attachments.uploader',
            'workflowInstance.workflowTemplate.stages.approverRole',
            'workflowInstance.workflowTemplate.stages.specificUser',
            'workflowInstance.tasks.stage',
            'workflowInstance.tasks.approver',
            'workflowInstance.tasks.histories.actor',
            'workflowInstance.events',
        ]);

        $instance = $request->workflowInstance;
        $currentTask = $instance?->tasks->firstWhere('status', 'pending');
        $canDecide = $currentTask !== null && Gate::allows('decide', $currentTask);
        $formFields = $request->formTemplate?->fields() ?? [];

        return view('requests.show', [
            'serviceRequest' => $request,
            'instance' => $instance,
            'currentTask' => $currentTask,
            'canDecide' => $canDecide,
            'formFields' => $formFields,
        ]);
    }

    public function submit(ServiceRequest $request, RequestService $service): RedirectResponse
    {
        Gate::authorize('submit', $request);

        try {
            $service->submit($request, request()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', 'The request could not be submitted.');
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('requests.show', $request)
            ->with('status', "{$request->request_number} was submitted for approval.");
    }

    public function cancel(ServiceRequest $request, RequestService $service): RedirectResponse
    {
        Gate::authorize('cancel', $request);

        $reason = request()->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ])['reason'] ?? null;

        try {
            $service->cancel($request, request()->user(), $reason);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('requests.show', $request)
            ->with('status', "{$request->request_number} was cancelled.");
    }

    public function download(
        ServiceRequest $request,
        RequestAttachment $attachment,
        AttachmentService $attachments,
    ): BinaryFileResponse {
        abort_unless((int) $attachment->request_id === (int) $request->getKey(), 404);

        Gate::authorize('downloadAttachment', $request);

        $path = $attachments->absolutePath($attachment);

        abort_if($path === null, 404, 'The stored file could not be found.');

        return response()->download($path, $attachment->original_filename, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
