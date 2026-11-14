<?php

namespace Tests\Feature\Livewire;

use App\Livewire\WorkflowEditor;
use App\Models\RequestType;
use App\Models\User;
use App\Models\WorkflowTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkflowEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function leaveType(): RequestType
    {
        return RequestType::where('code', 'LEAVE')->firstOrFail();
    }

    private function activeLeaveWorkflow(): WorkflowTemplate
    {
        return WorkflowTemplate::where('request_type_id', $this->leaveType()->getKey())
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function test_the_editor_is_closed_to_users_without_the_workflow_permission(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'))
            ->get(route('workflows.create', $this->leaveType()))
            ->assertForbidden();
    }

    public function test_publishing_a_new_version_supersedes_the_previous_one(): void
    {
        $admin = $this->user('admin@arunika.test');
        $previous = $this->activeLeaveWorkflow();

        $this->actingAs($admin);

        Livewire::test(WorkflowEditor::class, ['type' => $this->leaveType()])
            ->set('name', 'Leave approval (two stages)')
            ->set('stages.0.stage_name', 'Department Manager')
            ->set('stages.0.approver_role_id', \App\Models\Role::where('name', 'department_manager')->value('id'))
            ->set('stages.0.sla_hours', 24)
            ->call('addStage')
            ->set('stages.1.stage_name', 'HR Review')
            ->set('stages.1.approver_role_id', \App\Models\Role::where('name', 'process_admin')->value('id'))
            ->set('stages.1.approver_scope', 'global')
            ->set('stages.1.sla_hours', 48)
            ->call('publish')
            ->assertHasNoErrors();

        $published = WorkflowTemplate::where('request_type_id', $this->leaveType()->getKey())
            ->where('is_active', true)
            ->firstOrFail();

        $this->assertNotSame($previous->getKey(), $published->getKey());
        $this->assertSame(2, $published->version);
        $this->assertNotNull($published->published_at);
        $this->assertCount(2, $published->stages);

        $this->assertFalse($previous->refresh()->is_active);
        $this->assertNotNull($previous->superseded_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'workflow.published',
            'entity_type' => 'WorkflowTemplate',
            'entity_id' => $published->getKey(),
        ]);
    }

    public function test_a_published_version_cannot_be_edited(): void
    {
        $admin = $this->user('admin@arunika.test');
        $published = $this->activeLeaveWorkflow();

        $this->actingAs($admin);

        Livewire::test(WorkflowEditor::class, ['template' => $published])
            ->assertSet('locked', true)
            ->set('name', 'Renamed after publishing')
            ->call('saveDraft')
            ->assertHasErrors('workflow');

        $this->assertNotSame('Renamed after publishing', $published->refresh()->name);
    }

    public function test_cloning_a_published_version_creates_an_inactive_draft(): void
    {
        $admin = $this->user('admin@arunika.test');
        $published = $this->activeLeaveWorkflow();

        $this->actingAs($admin)
            ->post(route('workflows.version', $published))
            ->assertRedirect();

        $draft = WorkflowTemplate::where('request_type_id', $this->leaveType()->getKey())
            ->where('is_active', false)
            ->latest('version')
            ->firstOrFail();

        $this->assertSame(2, $draft->version);
        $this->assertSame($published->stages()->count(), $draft->stages()->count());
        $this->assertTrue($published->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'workflow.version_created',
            'entity_id' => $draft->getKey(),
        ]);
    }

    public function test_a_specific_user_stage_requires_a_named_person(): void
    {
        $admin = $this->user('admin@arunika.test');

        $this->actingAs($admin);

        Livewire::test(WorkflowEditor::class, ['type' => $this->leaveType()])
            ->set('stages.0.approver_scope', 'specific_user')
            ->set('stages.0.specific_user_id', null)
            ->call('publish')
            ->assertHasErrors('stages.0.specific_user_id');
    }
}
