<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\RequestStatus;
use App\Models\FormTemplate;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\FormDataValidator;
use App\Support\RequestNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestService
{
    public function __construct(
        private readonly RequestNumberGenerator $numbers,
        private readonly FormDataValidator $forms,
        private readonly WorkflowEngine $engine,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create or update a draft/revision. Drafts validate field types but not
     * required-ness, so a half-filled form can be saved and returned to later.
     *
     * @param  array{title?: string, description?: ?string, priority?: string, form_data?: array<string, mixed>}  $data
     */
    public function save(User $actor, RequestType $type, array $data, ?ServiceRequest $request = null): ServiceRequest
    {
        if (! $type->is_active) {
            throw ValidationException::withMessages(['request_type' => 'This request type is not available.']);
        }

        $template = $type->activeFormTemplate();

        if (! $template) {
            throw ValidationException::withMessages(['request_type' => 'No active form is configured for this request type.']);
        }

        if ($request) {
            $this->assertEditable($request, $actor);
        }

        $validated = validator($data, [
            'title' => ['required', 'string', 'min:5', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', \Illuminate\Validation\Rule::enum(Priority::class)],
            'form_data' => ['nullable', 'array'],
        ], [
            'title.required' => 'Give the request a short, specific title.',
            'title.min' => 'The title must be at least 5 characters.',
        ])->validate();

        $formData = $this->forms->validate($template, $validated['form_data'] ?? [], enforceRequired: false);
        $priority = Priority::tryFrom((string) ($validated['priority'] ?? '')) ?? Priority::Normal;

        return DB::transaction(function () use ($actor, $type, $template, $validated, $formData, $priority, $request) {
            if ($request === null) {
                $request = ServiceRequest::create([
                    'request_number' => $this->numbers->next(),
                    'request_type_id' => $type->getKey(),
                    'form_template_id' => $template->getKey(),
                    'requester_id' => $actor->getKey(),
                    'department_id' => $actor->department_id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'form_data' => $formData,
                    'priority' => $priority,
                    'status' => RequestStatus::Draft,
                ]);

                $this->audit->log('request.draft_created', $request, [
                    'request_number' => $request->request_number,
                    'request_type' => $type->name,
                ], $actor);
            } else {
                $request->forceFill([
                    'form_template_id' => $template->getKey(),
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'form_data' => $formData,
                    'priority' => $priority,
                ])->save();

                $this->audit->log('request.updated', $request, [
                    'request_number' => $request->request_number,
                ], $actor);
            }

            return $request;
        });
    }

    /**
     * Submit a draft or a revised request. Everything that changes workflow
     * state happens in one transaction, including number allocation on create.
     */
    public function submit(ServiceRequest $request, User $actor): ServiceRequest
    {
        $this->assertEditable($request, $actor);

        $template = $request->formTemplate;
        $type = $request->requestType;

        if (! $template) {
            throw ValidationException::withMessages(['form_data' => 'This request has no form template pinned and cannot be submitted.']);
        }

        if (! $type->activeWorkflowTemplate()) {
            throw ValidationException::withMessages([
                'workflow' => 'No active approval workflow is configured for this request type. Contact a process administrator.',
            ]);
        }

        $this->forms->validate($template, $request->form_data ?? [], enforceRequired: true);
        $this->assertRequiredAttachmentsPresent($request, $template);

        $wasRevision = $request->status === RequestStatus::RevisionRequired;

        return DB::transaction(function () use ($request, $actor, $wasRevision) {
            /** @var ServiceRequest $locked */
            $locked = ServiceRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [RequestStatus::Draft, RequestStatus::RevisionRequired], true)) {
                throw ValidationException::withMessages(['request' => 'This request is no longer editable.']);
            }

            if ($wasRevision) {
                $this->engine->resumeAfterRevision($locked);
            } else {
                $locked->forceFill([
                    'status' => RequestStatus::Submitted,
                    'submitted_at' => now(),
                ])->save();

                $this->engine->start($locked);
            }

            $this->audit->log($wasRevision ? 'request.resubmitted' : 'request.submitted', $locked, [
                'request_number' => $locked->request_number,
            ], $actor);

            $this->notifications->notify(
                $actor,
                $wasRevision ? 'request.resubmitted' : 'request.submitted',
                $wasRevision ? 'Revision submitted' : 'Request submitted',
                "{$locked->request_number} is now in the approval process.",
                $locked,
            );

            return $locked->refresh()->load('workflowInstance.tasks');
        });
    }

    public function cancel(ServiceRequest $request, User $actor, ?string $reason = null): ServiceRequest
    {
        if ((int) $request->requester_id !== (int) $actor->getKey()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only the requester can cancel this request.');
        }

        if (! $request->isCancellable()) {
            throw ValidationException::withMessages(['request' => 'Only open requests can be cancelled.']);
        }

        return DB::transaction(function () use ($request, $actor, $reason) {
            /** @var ServiceRequest $locked */
            $locked = ServiceRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isCancellable()) {
                throw ValidationException::withMessages(['request' => 'Only open requests can be cancelled.']);
            }

            $instance = $locked->workflowInstance()->first();

            if ($instance) {
                $this->engine->terminate($instance, RequestStatus::Cancelled, $reason ?? 'Cancelled by requester.', $actor);
            } else {
                $locked->forceFill(['status' => RequestStatus::Cancelled, 'completed_at' => now()])->save();
                $this->audit->log('request.cancelled', $locked, ['request_number' => $locked->request_number], $actor);
            }

            return $locked->refresh();
        });
    }

    public function assertEditable(ServiceRequest $request, User $actor): void
    {
        if ((int) $request->requester_id !== (int) $actor->getKey()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You can only edit your own requests.');
        }

        if (! $request->isEditable()) {
            throw ValidationException::withMessages([
                'request' => 'Submitted requests cannot be edited directly. Ask for a revision instead.',
            ]);
        }
    }

    private function assertRequiredAttachmentsPresent(ServiceRequest $request, FormTemplate $template): void
    {
        $requiredFileFields = collect($template->fields())
            ->filter(fn (array $field) => ($field['type'] ?? null) === 'file' && ($field['required'] ?? false));

        if ($requiredFileFields->isEmpty()) {
            return;
        }

        $presentKeys = $request->attachments()->pluck('field_key')->filter()->all();

        foreach ($requiredFileFields as $field) {
            if (! in_array($field['key'], $presentKeys, true)) {
                throw ValidationException::withMessages([
                    "files.{$field['key']}" => 'Attach the required file for '.($field['label'] ?? $field['key']).'.',
                ]);
            }
        }
    }
}
