<?php

namespace App\Livewire;

use App\Models\FormTemplate;
use App\Models\RequestType;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Form template editor')]
class FormTemplateEditor extends Component
{
    public const FIELD_TYPES = [
        'text' => 'Short text',
        'textarea' => 'Long text',
        'number' => 'Number',
        'date' => 'Date',
        'dropdown' => 'Dropdown',
        'radio' => 'Single choice',
        'checkbox' => 'Multiple choice',
        'file' => 'File upload',
    ];

    public ?int $templateId = null;

    public int $typeId = 0;

    public string $typeName = '';

    public string $typeCode = '';

    public string $name = '';

    public int $version = 0;

    /** @var array<int, array<string, mixed>> */
    public array $fields = [];

    public bool $locked = false;

    public bool $isPublished = false;

    public function mount(RequestType $requestType, ?FormTemplate $template = null): void
    {
        $this->typeId = $requestType->getKey();
        $this->typeName = $requestType->name;
        $this->typeCode = $requestType->code;

        if ($template !== null) {
            abort_unless($template->request_type_id === $requestType->getKey(), 404);

            $this->templateId = $template->getKey();
            $this->name = $template->name;
            $this->version = $template->version;
            $this->isPublished = $template->published_at !== null;
            $this->locked = $this->isPublished;
            $this->fields = array_map(
                fn (array $field) => $this->toState($field),
                $template->fields(),
            );

            return;
        }

        $previous = $requestType->formTemplates()->orderByDesc('version')->first();

        $this->name = $previous?->name ?? $requestType->name.' form';
        $this->version = ((int) $requestType->formTemplates()->max('version')) + 1;
        $this->fields = $previous !== null
            ? array_map(fn (array $field) => $this->toState($field), $previous->fields())
            : [$this->blankField()];
    }

    public function addField(string $type = 'text'): void
    {
        $this->fields[] = $this->blankField($type);
    }

    public function removeField(int $index): void
    {
        if (count($this->fields) <= 1) {
            $this->addError('fields', 'A form needs at least one field.');

            return;
        }

        array_splice($this->fields, $index, 1);
    }

    public function moveField(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->fields[$target])) {
            return;
        }

        [$this->fields[$index], $this->fields[$target]] = [$this->fields[$target], $this->fields[$index]];
    }

    public function saveDraft(AuditLogger $audit): void
    {
        $this->persist($audit, false);
    }

    public function publish(AuditLogger $audit): void
    {
        $this->persist($audit, true);
    }

    public function render()
    {
        return view('livewire.form-template-editor', [
            'fieldTypes' => self::FIELD_TYPES,
            'choiceTypes' => ['dropdown', 'radio', 'checkbox'],
        ]);
    }

    private function persist(AuditLogger $audit, bool $publish): void
    {
        if ($this->locked) {
            $this->addError('form', 'This version is published and can no longer change. Start a new version instead.');

            return;
        }

        foreach ($this->fields as $index => $field) {
            if (blank($field['key']) && filled($field['label'])) {
                $this->fields[$index]['key'] = Str::slug($field['label'], '_');
            }
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'fields.*.type' => ['required', Rule::in(array_keys(self::FIELD_TYPES))],
            'fields.*.required' => ['boolean'],
            'fields.*.help' => ['nullable', 'string', 'max:255'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:120'],
            'fields.*.options' => ['nullable', 'string', 'max:2000'],
        ], [
            'fields.*.key.regex' => 'Keys use lowercase letters, numbers and underscores, starting with a letter.',
        ]);

        $seen = [];
        foreach ($this->fields as $index => $field) {
            $key = $field['key'];

            if (isset($seen[$key])) {
                $this->addError("fields.{$index}.key", 'This key is already used by another field.');
            }

            $seen[$key] = true;

            if (in_array($field['type'], ['dropdown', 'radio', 'checkbox'], true)
                && $this->options($field) === []) {
                $this->addError("fields.{$index}.options", 'Add at least one option, one per line.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $schema = [
            'fields' => array_map(fn (array $field) => [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => (bool) $field['required'],
                'help' => filled($field['help']) ? $field['help'] : null,
                'placeholder' => filled($field['placeholder']) ? $field['placeholder'] : null,
                'options' => in_array($field['type'], ['dropdown', 'radio', 'checkbox'], true)
                    ? $this->options($field)
                    : [],
            ], $this->fields),
        ];

        $template = DB::transaction(function () use ($audit, $publish, $schema) {
            $template = $this->templateId
                ? FormTemplate::query()->lockForUpdate()->findOrFail($this->templateId)
                : null;

            if ($template !== null && $template->published_at !== null) {
                $this->addError('form', 'This version is published and can no longer change. Start a new version instead.');

                return null;
            }

            if ($template === null) {
                $version = ((int) FormTemplate::query()
                    ->where('request_type_id', $this->typeId)
                    ->max('version')) + 1;

                $template = FormTemplate::create([
                    'request_type_id' => $this->typeId,
                    'name' => $this->name,
                    'version' => $version,
                    'schema' => $schema,
                    'is_active' => false,
                    'created_by' => auth()->id(),
                ]);

                $this->templateId = $template->getKey();
                $this->version = $template->version;
            } else {
                $template->forceFill([
                    'name' => $this->name,
                    'schema' => $schema,
                ])->save();
            }

            if ($publish) {
                FormTemplate::query()
                    ->where('request_type_id', $template->request_type_id)
                    ->whereKeyNot($template->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $template->forceFill([
                    'is_active' => true,
                    'published_at' => now(),
                ])->save();

                $this->locked = true;
                $this->isPublished = true;
            }

            $audit->log($publish ? 'form.published' : 'form.draft_saved', $template, [
                'request_type' => $this->typeCode,
                'version' => $template->version,
                'fields' => count($schema['fields']),
            ]);

            return $template;
        });

        if ($template === null) {
            return;
        }

        session()->flash(
            'status',
            $publish
                ? "Form version {$template->version} is now live for {$this->typeName}."
                : "Form version {$template->version} saved as a draft.",
        );

        $this->redirect(route('admin.request-types.forms.edit', [$this->typeCode, $template]), navigate: true);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function toState(array $field): array
    {
        return [
            'key' => (string) ($field['key'] ?? ''),
            'label' => (string) ($field['label'] ?? ''),
            'type' => (string) ($field['type'] ?? 'text'),
            'required' => (bool) ($field['required'] ?? false),
            'help' => (string) ($field['help'] ?? ''),
            'placeholder' => (string) ($field['placeholder'] ?? ''),
            'options' => implode("\n", array_map('strval', $field['options'] ?? [])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankField(string $type = 'text'): array
    {
        return [
            'key' => '',
            'label' => '',
            'type' => $type,
            'required' => true,
            'help' => '',
            'placeholder' => '',
            'options' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    private function options(array $field): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', (string) $field['options']) ?: []),
            fn (string $option) => $option !== '',
        ));
    }
}
