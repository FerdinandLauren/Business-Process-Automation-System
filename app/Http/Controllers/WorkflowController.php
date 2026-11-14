<?php

namespace App\Http\Controllers;

use App\Models\RequestType;
use App\Models\WorkflowTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function index(): View
    {
        $types = RequestType::query()
            ->with([
                'formTemplates' => fn ($query) => $query->orderByDesc('version'),
                'workflowTemplates' => fn ($query) => $query->withCount('stages')->orderByDesc('version'),
            ])
            ->orderBy('name')
            ->get();

        return view('workflows.index', [
            'types' => $types,
            'activeCount' => $types->filter(fn (RequestType $type) => $type->workflowTemplates->contains('is_active', true))->count(),
            'missingCount' => $types->filter(fn (RequestType $type) => ! $type->workflowTemplates->contains('is_active', true))->count(),
        ]);
    }

    public function version(Request $request, WorkflowTemplate $template, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('workflow.manage'), 403);

        $draft = DB::transaction(function () use ($template, $audit) {
            $template->load('stages');

            $version = (int) WorkflowTemplate::query()
                ->where('request_type_id', $template->request_type_id)
                ->max('version') + 1;

            $draft = WorkflowTemplate::create([
                'request_type_id' => $template->request_type_id,
                'name' => $template->name,
                'description' => $template->description,
                'version' => $version,
                'is_active' => false,
                'created_by' => auth()->id(),
            ]);

            foreach ($template->stages as $stage) {
                $draft->stages()->create([
                    'stage_order' => $stage->stage_order,
                    'stage_name' => $stage->stage_name,
                    'approver_role_id' => $stage->approver_role_id,
                    'approver_scope' => $stage->approver_scope,
                    'specific_user_id' => $stage->specific_user_id,
                    'sla_hours' => $stage->sla_hours,
                    'is_active' => $stage->is_active,
                ]);
            }

            $audit->log('workflow.version_created', $draft, [
                'request_type' => $template->requestType->code,
                'from_version' => $template->version,
                'version' => $draft->version,
            ]);

            return $draft;
        });

        session()->flash('status', "Version {$draft->version} was cloned from version {$template->version}. Review it before publishing.");

        return redirect()->route('workflows.edit', $draft);
    }
}
