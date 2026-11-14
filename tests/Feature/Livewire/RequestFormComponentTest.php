<?php

namespace Tests\Feature\Livewire;

use App\Enums\RequestStatus;
use App\Livewire\RequestForm;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RequestFormComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('bpa.attachments.disk'));

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

    private function fillLeaveForm($component)
    {
        return $component
            ->set('title', 'Annual leave for the December break')
            ->set('description', 'Handover agreed with the team lead.')
            ->set('priority', 'normal')
            ->set('formData.leave_type', 'Annual leave')
            ->set('formData.start_date', '2026-12-08')
            ->set('formData.end_date', '2026-12-12')
            ->set('formData.days', '5')
            ->set('formData.handover', 'Maya Sutedja')
            ->set('formData.reason', 'Family holiday.');
    }

    public function test_submitting_through_the_component_starts_the_workflow(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'));

        $component = Livewire::test(RequestForm::class, ['type' => $this->leaveType()]);

        $this->fillLeaveForm($component)->call('submitRequest');

        $request = ServiceRequest::latest('id')->firstOrFail();

        $component->assertRedirect(route('requests.show', $request));

        $this->assertSame(RequestStatus::UnderReview, $request->status);
        $this->assertNotNull($request->workflowInstance);
        $this->assertSame(1, $request->workflowInstance->tasks()->where('status', 'pending')->count());
    }

    public function test_saving_a_draft_does_not_start_the_workflow(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'));

        $component = Livewire::test(RequestForm::class, ['type' => $this->leaveType()]);

        $component->set('title', 'Draft leave request for the year-end break')->call('saveDraft');

        $request = ServiceRequest::latest('id')->firstOrFail();

        $component->assertRedirect(route('requests.show', $request));

        $this->assertSame(RequestStatus::Draft, $request->status);
        $this->assertNull($request->workflowInstance);
    }

    public function test_the_component_surfaces_required_field_errors(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'));

        Livewire::test(RequestForm::class, ['type' => $this->leaveType()])
            ->set('title', 'Incomplete leave request')
            ->call('submitRequest')
            ->assertHasErrors(['formData.leave_type', 'formData.start_date', 'formData.handover'])
            ->assertSee('Saved as a draft');

        // The draft is kept so the requester can finish it later; the workflow is not started.
        $draft = ServiceRequest::where('title', 'Incomplete leave request')->firstOrFail();

        $this->assertSame(RequestStatus::Draft, $draft->status);
        $this->assertNull($draft->workflowInstance);
    }

    public function test_a_missing_required_attachment_is_shown_on_the_file_field(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'));

        Livewire::test(RequestForm::class, [
            'type' => RequestType::where('code', 'REIMB')->firstOrFail(),
        ])
            ->set('title', 'Taxi fares for the client workshop')
            ->set('formData.expense_category', 'Travel')
            ->set('formData.amount', '450000')
            ->set('formData.expense_date', '2026-11-10')
            ->set('formData.cost_centre', 'HR-OPS-24')
            ->call('submitRequest')
            ->assertHasErrors(['fieldFiles.receipt']);
    }

    public function test_the_title_is_required(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'));

        Livewire::test(RequestForm::class, ['type' => $this->leaveType()])
            ->call('saveDraft')
            ->assertHasErrors(['title']);
    }

    public function test_an_employee_without_the_create_permission_cannot_open_the_form(): void
    {
        $this->actingAs($this->user('admin@arunika.test'));

        $this->get(route('requests.create', $this->leaveType()))->assertForbidden();
    }
}
