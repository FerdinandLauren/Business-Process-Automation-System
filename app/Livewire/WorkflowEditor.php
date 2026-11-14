<?php

namespace App\Livewire;

use App\Enums\ApproverScope;
use App\Models\RequestType;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowTemplate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Workflow editor')]
class WorkflowEditor extends Component
{
    public ?int $templateId = null;

    public int $typeId = 0;

    public string $typeName = '';

    public string $name = '';

    public string $description = '';

    public int $version = 0;

    /** @var array<int, array<string, mixed>> */
    public array $stages = [];

    public bool $locked = false;

    public bool $isPublished = false;

    public function mount(?RequestType $type = null, ?WorkflowTemplate $template = null): void
    {
        // Livewire's dependency injection can hand us an empty model instance for an
        // optional parameter that was not passed. Treat that as "not provided".
        if ($template !== null && ! $template->exists) {
            $template = null;
        }

        if ($template !== null) {
            $template->load(['requestType', 'stages']);

            $this->templateId = $template->getKey();
            $this->typeId = $template->request_type_id;
            $this->typeName = $template->requestType->name;
            $this->name = $template->name;
            $this->description = (string) $template->description;
            $this->version = $template->version;
            $this->isPublished = $template->published_at !== null;
            $this->locked = $this->isPublished || $template->instances()->exists();
            $this->stages = $template->stages
                ->map(fn ($stage) => [
                    'stage_name' => $stage->stage_name,
                    'approver_role_id' => $stage->approver_role_id,
                    'approver_scope' => $stage->approver_scope->value,
                    'specific_user_id' => $stage->specific_user_id,
                    'sla_hours' => $stage->sla_hours,
                ])
                ->all();

            return;
        }

        abort_if($type === null, 404);

        $this->typeId = $type->getKey();
        $this->typeName = $type->name;
        $this->name = $type->name.' approval';
        $this->description = 'Sequential approval route for '.strtolower($type->name).' requests.';
        $this->addStage();
    }

    public function addStage(): void
    {
        $this->stages[] = [
            'stage_name' => 'Approval stage '.(count($this->stages) + 1),
            'approver_role_id' => Role::query()->where('name', 'department_manager')->value('id'),
            'approver_scope' => ApproverScope::RequesterDepartment->value,
            'specific_user_id' => null,
            'sla_hours' => 24,
        ];
    }

    public function removeStage(int $index): void
    {
        if (count($this->stages) <= 1) {
            $this->addError('stages', 'A workflow needs at least one approval stage.');

            return;
        }

        array_splice($this->stages, $index, 1);
    }

    public function moveStage(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->stages[$target])) {
            return;
        }

        [$this->stages[$index], $this->stages[$target]] = [$this->stages[$target], $this->stages[$index]];
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
        return view('livewire.workflow-editor', [
            'roles' => Role::query()->orderBy('label')->get(),
            'users' => User::query()->active()->with('department')->orderBy('name')->get(),
            'scopeOptions' => [
                ApproverScope::RequesterDepartment->value => 'Manager of the requester department',
                ApproverScope::Global->value => 'Any user holding the role',
                ApproverScope::SpecificUser->value => 'One named user',
            ],
        ]);
    }

    private function persist(AuditLogger $audit, bool $publish): void
    {
        if ($this->locked) {
            $this->addError('workflow', 'This version is already in use. Create a new version to change the route.');

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.stage_name' => ['required', 'string', 'max:80'],
            'stages.*.approver_role_id' => ['required', 'exists:roles,id'],
            'stages.*.approver_scope' => ['required', Rule::enum(ApproverScope::class)],
            'stages.*.specific_user_id' => ['nullable', 'exists:users,id'],
            'stages.*.sla_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);

        foreach ($this->stages as $index => $stage) {
            if ($stage['approver_scope'] === ApproverScope::SpecificUser->value && blank($stage['specific_user_id'])) {
                $this->addError("stages.{$index}.specific_user_id", 'Choose the person who will approve this stage.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $template = DB::transaction(function () use ($publish, $audit) {
            $template = $this->templateId
                ? WorkflowTemplate::query()->lockForUpdate()->findOrFail($this->templateId)
                : null;

            if ($template !== null && ($template->published_at !== null || $template->instances()->exists())) {
                $this->addError('workflow', 'This version is already in use. Create a new version to change the route.');

                return null;
            }

            if ($template === null) {
                $version = (int) WorkflowTemplate::query()
                    ->where('request_type_id', $this->typeId)
                    ->max('version') + 1;

                $template = WorkflowTemplate::create([
                    'request_type_id' => $this->typeId,
                    'name' => $this->name,
                    'description' => $this->description,
                    'version' => $version,
                    'is_active' => false,
                    'created_by' => auth()->id(),
                ]);

                $this->templateId = $template->getKey();
                $this->version = $template->version;
            } else {
                $template->forceFill([
                    'name' => $this->name,
                    'description' => $this->description,
                ])->save();
            }

            $template->stages()->delete();

            foreach ($this->stages as $index => $stage) {
                $template->stages()->create([
                    'stage_order' => $index + 1,
                    'stage_name' => $stage['stage_name'],
                    'approver_role_id' => $stage['approver_role_id'],
                    'approver_scope' => $stage['approver_scope'],
                    'specific_user_id' => $stage['approver_scope'] === ApproverScope::SpecificUser->value
                        ? $stage['specific_user_id']
                        : null,
                    'sla_hours' => $stage['sla_hours'],
                    'is_active' => true,
                ]);
            }

            if ($publish) {
                WorkflowTemplate::query()
                    ->where('request_type_id', $template->request_type_id)
                    ->whereKeyNot($template->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'superseded_at' => now()]);

                $template->forceFill([
                    'is_active' => true,
                    'published_at' => now(),
                    'superseded_at' => null,
                ])->save();
            }

            $audit->log($publish ? 'workflow.published' : 'workflow.draft_saved', $template, [
                'request_type' => $template->requestType->code,
                'version' => $template->version,
                'stages' => count($this->stages),
            ]);

            return $template;
        });

        if ($template === null) {
            return;
        }

        session()->flash(
            'status',
            $publish
                ? "Workflow version {$template->version} is now active for {$this->typeName}."
                : "Workflow version {$template->version} saved as a draft.",
        );

        $this->redirect(route('workflows.edit', $template), navigate: true);
    }
}
