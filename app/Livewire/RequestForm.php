<?php

namespace App\Livewire;

use App\Enums\Priority;
use App\Exceptions\WorkflowException;
use App\Models\FormTemplate;
use App\Models\RequestAttachment;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\WorkflowTemplate;
use App\Services\AttachmentService;
use App\Services\RequestService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Request form')]
class RequestForm extends Component
{
    use WithFileUploads;

    public ?int $requestId = null;

    public int $typeId = 0;

    public string $title = '';

    public string $description = '';

    public string $priority = 'normal';

    /** @var array<string, mixed> */
    public array $formData = [];

    /** @var array<string, mixed> */
    public array $fieldFiles = [];

    /** @var array<int, mixed> */
    public array $files = [];

    public function mount(?RequestType $type = null, ?ServiceRequest $request = null): void
    {
        // Livewire's dependency injection can hand us an empty model instance for an
        // optional parameter that was not passed. Treat that as "not provided".
        if ($request !== null && ! $request->exists) {
            $request = null;
        }

        if ($request !== null) {
            Gate::authorize('update', $request);

            $this->requestId = $request->getKey();
            $this->typeId = $request->request_type_id;
            $this->title = $request->title;
            $this->description = (string) $request->description;
            $this->priority = $request->priority->value;
            $this->formData = $this->normalizeData($request->formTemplate, $request->form_data ?? []);

            return;
        }

        Gate::authorize('create', ServiceRequest::class);

        abort_if($type === null, 404);

        $template = $type->activeFormTemplate();
        abort_if($template === null, 404);

        $this->typeId = $type->getKey();
        $this->formData = $this->normalizeData($template, []);
    }

    public function saveDraft(RequestService $service): void
    {
        $this->persist($service, false);
    }

    public function submitRequest(RequestService $service): void
    {
        $this->persist($service, true);
    }

    public function removeAttachment(int $attachmentId, AttachmentService $attachments): void
    {
        $attachment = RequestAttachment::findOrFail($attachmentId);
        $request = ServiceRequest::findOrFail($attachment->request_id);

        Gate::authorize('update', $request);

        $attachments->delete($attachment, auth()->user());

        session()->flash('status', 'Attachment removed.');
    }

    public function render()
    {
        $type = $this->requestType();
        $template = $this->formTemplate();
        $request = $this->requestId ? ServiceRequest::find($this->requestId) : null;

        return view('livewire.request-form', [
            'type' => $type,
            'request' => $request,
            'fields' => $template?->fields() ?? [],
            'stages' => $this->stagePreview($request),
            'attachments' => $request
                ? $request->attachments()->orderBy('id')->get()
                : collect(),
            'maxKb' => (int) config('bpa.attachments.max_kb'),
            'allowedExtensions' => config('bpa.attachments.extensions', []),
        ]);
    }

    private function persist(RequestService $service, bool $submit): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'files.*' => ['file', 'max:'.(int) config('bpa.attachments.max_kb')],
            'fieldFiles.*' => ['file', 'max:'.(int) config('bpa.attachments.max_kb')],
        ], [
            'title.required' => 'Give the request a short, specific title.',
            'title.min' => 'The title must be at least 5 characters.',
        ]);

        $type = $this->requestType();
        abort_if($type === null, 404);

        $existing = $this->requestId ? ServiceRequest::findOrFail($this->requestId) : null;

        if ($existing) {
            Gate::authorize('update', $existing);
        }

        try {
            $request = $service->save(auth()->user(), $type, [
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $this->priority,
                'form_data' => $this->formData,
            ], $existing);

            $this->requestId = $request->getKey();
            $attachmentService = app(AttachmentService::class);

            foreach ($this->files as $file) {
                $attachmentService->store($file, $request, auth()->user());
            }

            foreach ($this->fieldFiles as $key => $file) {
                if ($file) {
                    $attachmentService->store($file, $request, auth()->user(), $key);
                }
            }

            $this->reset('files', 'fieldFiles');

            if ($submit) {
                $service->submit($request->refresh(), auth()->user());
                session()->flash('status', "{$request->request_number} was submitted for approval.");

                $this->redirect(route('requests.show', $request), navigate: true);

                return;
            }

            session()->flash('status', "Draft {$request->request_number} saved.");
            $this->redirect(route('requests.show', $request), navigate: true);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages($this->mapErrors($e->errors()));
        } catch (WorkflowException $e) {
            $this->addError('workflow', $e->getMessage());
        }
    }

    /**
     * Service-level validation errors use the raw field keys (for example
     * "leave_type" or "files.receipt"). The form binds them under "formData.*"
     * and "fieldFiles.*", so translate the keys before showing them.
     *
     * @param  array<string, array<int, string>>  $errors
     * @return array<string, array<int, string>>
     */
    private function mapErrors(array $errors): array
    {
        $fieldKeys = collect($this->formTemplate()?->fields() ?? [])
            ->pluck('key')
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->all();

        $mapped = [];

        foreach ($errors as $key => $messages) {
            if (str_starts_with($key, 'form_data.')) {
                $key = 'formData.'.substr($key, strlen('form_data.'));
            } elseif (str_starts_with($key, 'files.')) {
                $key = 'fieldFiles.'.substr($key, strlen('files.'));
            } elseif (in_array($key, $fieldKeys, true)) {
                $key = 'formData.'.$key;
            }

            $mapped[$key] = $messages;
        }

        return $mapped;
    }

    private function requestType(): ?RequestType
    {
        return $this->typeId ? RequestType::find($this->typeId) : null;
    }

    private function formTemplate(): ?FormTemplate
    {
        if ($this->requestId) {
            $request = ServiceRequest::with('formTemplate')->find($this->requestId);

            if ($request?->formTemplate) {
                return $request->formTemplate;
            }
        }

        return $this->requestType()?->activeFormTemplate();
    }

    /**
     * @return Collection<int, \App\Models\WorkflowStage>
     */
    private function stagePreview(?ServiceRequest $request): Collection
    {
        if ($request?->workflowInstance) {
            return $request->workflowInstance->workflowTemplate->stages()->get();
        }

        $template = $this->requestType()?->activeWorkflowTemplate();

        return $template ? $template->stages()->get() : collect();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeData(?FormTemplate $template, array $data): array
    {
        $normalized = [];

        foreach ($template?->fields() ?? [] as $field) {
            $key = $field['key'];
            $normalized[$key] = $data[$key] ?? (($field['type'] ?? 'text') === 'checkbox' ? [] : '');
        }

        return $normalized;
    }
}
