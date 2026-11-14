<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RequestTypeController extends Controller
{
    public function index(): View
    {
        $types = RequestType::query()
            ->withCount('requests')
            ->with([
                'formTemplates' => fn ($query) => $query->orderByDesc('version'),
                'workflowTemplates' => fn ($query) => $query->withCount('stages')->where('is_active', true),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.request-types.index', [
            'types' => $types,
            'activeCount' => $types->where('is_active', true)->count(),
            'formGaps' => $types->filter(fn (RequestType $type) => ! $type->formTemplates->contains('is_active', true))->count(),
            'routeGaps' => $types->filter(fn (RequestType $type) => $type->workflowTemplates->isEmpty())->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.request-types.form', [
            'requestType' => new RequestType(['is_active' => true]),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $type = RequestType::create($this->validated($request));

        $audit->log('request_type.created', $type, ['code' => $type->code]);

        return redirect()
            ->route('admin.request-types.edit', $type)
            ->with('status', "{$type->name} was added to the catalogue. Publish a form so employees can raise it.");
    }

    public function edit(RequestType $requestType): View
    {
        return view('admin.request-types.form', [
            'requestType' => $requestType->loadCount('requests')->load([
                'formTemplates' => fn ($query) => $query->withCount('requests')->orderByDesc('version'),
                'workflowTemplates' => fn ($query) => $query->withCount('stages')->orderByDesc('version'),
            ]),
        ]);
    }

    public function update(Request $request, RequestType $requestType, AuditLogger $audit): RedirectResponse
    {
        $before = $requestType->only(['name', 'code', 'description', 'is_active']);
        $requestType->update($this->validated($request, $requestType));

        $audit->log('request_type.updated', $requestType, [
            'before' => $before,
            'after' => $requestType->only(['name', 'code', 'description', 'is_active']),
        ]);

        return redirect()
            ->route('admin.request-types.edit', $requestType)
            ->with('status', "{$requestType->name} was updated.");
    }

    public function toggleStatus(Request $request, RequestType $requestType, AuditLogger $audit): RedirectResponse
    {
        $requestType->forceFill(['is_active' => ! $requestType->is_active])->save();

        $audit->log($requestType->is_active ? 'request_type.activated' : 'request_type.deactivated', $requestType, [
            'code' => $requestType->code,
        ]);

        return back()->with('status', $requestType->is_active
            ? "{$requestType->name} is available for new requests again."
            : "{$requestType->name} was retired. Existing requests keep their history and approvals.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?RequestType $requestType = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('request_types', 'name')->ignore($requestType?->getKey())],
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9-]+$/', Rule::unique('request_types', 'code')->ignore($requestType?->getKey())],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ], [
            'code.regex' => 'Use uppercase letters, numbers and hyphens only.',
        ]);
    }
}
