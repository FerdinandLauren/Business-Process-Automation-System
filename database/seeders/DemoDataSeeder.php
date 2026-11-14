<?php

namespace Database\Seeders;

use App\Enums\ApproverScope;
use App\Enums\Priority;
use App\Models\ApprovalTask;
use App\Models\Department;
use App\Models\FormTemplate;
use App\Models\RequestType;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkflowTemplate;
use App\Services\ApprovalService;
use App\Services\AttachmentService;
use App\Services\RequestService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'name');
        $users = User::pluck('id', 'email');
        $departments = Department::pluck('id', 'code');
        $admin = User::where('email', 'admin@arunika.test')->firstOrFail();

        $types = [
            [
                'name' => 'Reimbursement',
                'code' => 'REIMB',
                'description' => 'Claim business expenses already paid personally, with receipts and cost-centre allocation.',
                'fields' => [
                    ['key' => 'expense_category', 'label' => 'Expense category', 'type' => 'dropdown', 'required' => true, 'options' => ['Travel', 'Accommodation', 'Client entertainment', 'Office supplies', 'Other']],
                    ['key' => 'amount', 'label' => 'Claim amount (IDR)', 'type' => 'number', 'required' => true, 'help' => 'Use the total printed on the receipt, excluding personal items.'],
                    ['key' => 'expense_date', 'label' => 'Expense date', 'type' => 'date', 'required' => true],
                    ['key' => 'cost_centre', 'label' => 'Cost centre', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. HR-OPS-24'],
                    ['key' => 'receipt', 'label' => 'Receipt or invoice', 'type' => 'file', 'required' => true, 'help' => 'A legible scan or photo of the original receipt.'],
                    ['key' => 'notes', 'label' => 'Additional notes', 'type' => 'textarea', 'required' => false],
                ],
                'stages' => [
                    ['Department Manager', 'department_manager', ApproverScope::RequesterDepartment, null, 24],
                    ['Finance Manager', 'finance_manager', ApproverScope::Global, null, 48],
                    ['Finance Processing', 'finance_admin', ApproverScope::Global, null, 72],
                ],
            ],
            [
                'name' => 'Leave Request',
                'code' => 'LEAVE',
                'description' => 'Request annual, sick or special leave. Balances are confirmed by the department manager.',
                'fields' => [
                    ['key' => 'leave_type', 'label' => 'Leave type', 'type' => 'dropdown', 'required' => true, 'options' => ['Annual leave', 'Sick leave', 'Special leave', 'Unpaid leave']],
                    ['key' => 'start_date', 'label' => 'First day of leave', 'type' => 'date', 'required' => true],
                    ['key' => 'end_date', 'label' => 'Last day of leave', 'type' => 'date', 'required' => true],
                    ['key' => 'days', 'label' => 'Working days requested', 'type' => 'number', 'required' => true],
                    ['key' => 'handover', 'label' => 'Handover contact', 'type' => 'text', 'required' => true, 'placeholder' => 'Who covers your duties?'],
                    ['key' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => false],
                ],
                'stages' => [
                    ['Department Manager', 'department_manager', ApproverScope::RequesterDepartment, null, 24],
                ],
            ],
            [
                'name' => 'IT Access Request',
                'code' => 'ITACC',
                'description' => 'Request new access, software or hardware for an internal system. Provisioning is logged by IT.',
                'fields' => [
                    ['key' => 'system_name', 'label' => 'System or application', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. SAP, email, VPN'],
                    ['key' => 'access_type', 'label' => 'Access type', 'type' => 'radio', 'required' => true, 'options' => ['New account', 'Additional permission', 'Software installation', 'Hardware']],
                    ['key' => 'business_need', 'label' => 'Business need', 'type' => 'textarea', 'required' => true],
                    ['key' => 'needed_by', 'label' => 'Needed by', 'type' => 'date', 'required' => true],
                    ['key' => 'acknowledgement', 'label' => 'Acceptable use', 'type' => 'checkbox', 'required' => true, 'options' => ['I accept the company acceptable use policy']],
                ],
                'stages' => [
                    ['Department Manager', 'department_manager', ApproverScope::RequesterDepartment, null, 24],
                    ['IT Manager', 'department_manager', ApproverScope::SpecificUser, 'rina@arunika.test', 16],
                    ['IT Provisioning', 'system_admin', ApproverScope::SpecificUser, 'admin@arunika.test', 8],
                ],
            ],
            [
                'name' => 'Purchase Request',
                'code' => 'PURCH',
                'description' => 'Raise a purchase for goods or services. Procurement confirms sourcing; finance confirms budget.',
                'fields' => [
                    ['key' => 'vendor', 'label' => 'Proposed vendor', 'type' => 'text', 'required' => false],
                    ['key' => 'amount', 'label' => 'Estimated value (IDR)', 'type' => 'number', 'required' => true],
                    ['key' => 'budget_line', 'label' => 'Budget line', 'type' => 'text', 'required' => true],
                    ['key' => 'required_by', 'label' => 'Required by', 'type' => 'date', 'required' => true],
                    ['key' => 'specification', 'label' => 'Specification and quantity', 'type' => 'textarea', 'required' => true],
                    ['key' => 'quotation', 'label' => 'Quotation', 'type' => 'file', 'required' => false],
                ],
                'stages' => [
                    ['Department Manager', 'department_manager', ApproverScope::RequesterDepartment, null, 24],
                    ['Procurement Review', 'employee', ApproverScope::SpecificUser, 'yusuf@arunika.test', 24],
                    ['Finance Manager', 'finance_manager', ApproverScope::Global, null, 48],
                    ['Finance Processing', 'finance_admin', ApproverScope::Global, null, 72],
                ],
            ],
        ];

        $created = [];

        foreach ($types as $definition) {
            $type = RequestType::create([
                'name' => $definition['name'],
                'code' => $definition['code'],
                'description' => $definition['description'],
                'is_active' => true,
            ]);

            FormTemplate::create([
                'request_type_id' => $type->getKey(),
                'name' => $type->name.' form',
                'version' => 1,
                'schema' => ['fields' => $definition['fields']],
                'is_active' => true,
                'published_at' => now()->subMonths(2),
                'created_by' => $admin->getKey(),
            ]);

            $workflow = WorkflowTemplate::create([
                'request_type_id' => $type->getKey(),
                'name' => $type->name.' approval',
                'description' => 'Sequential approval route for '.strtolower($type->name).' requests.',
                'version' => 1,
                'is_active' => true,
                'published_at' => now()->subMonths(2),
                'created_by' => $admin->getKey(),
            ]);

            foreach ($definition['stages'] as $index => [$name, $role, $scope, $specificEmail, $sla]) {
                $workflow->stages()->create([
                    'stage_order' => $index + 1,
                    'stage_name' => $name,
                    'approver_role_id' => $roles[$role],
                    'approver_scope' => $scope,
                    'specific_user_id' => $specificEmail ? $users[$specificEmail] : null,
                    'sla_hours' => $sla,
                    'is_active' => true,
                ]);
            }

            $created[$type->code] = $type;
        }

        $service = app(RequestService::class);
        $approvals = app(ApprovalService::class);
        $attachments = app(AttachmentService::class);

        $ferdinand = User::where('email', 'ferdinand@arunika.test')->firstOrFail();
        $rina = User::where('email', 'rina@arunika.test')->firstOrFail();

        /* 1. Fully approved reimbursement. */
        $approved = $this->createRequest($service, $attachments, $ferdinand, $created['REIMB'], [
            'title' => 'Client visit travel reimbursement — Bandung, October',
            'description' => 'Two-day client workshop in Bandung. Train fare, one night hotel and local transport, all pre-approved by the account lead.',
            'priority' => Priority::Normal,
            'form_data' => [
                'expense_category' => 'Travel',
                'amount' => '2450000',
                'expense_date' => now()->subDays(18)->toDateString(),
                'cost_centre' => 'HR-OPS-24',
                'notes' => 'Receipts consolidated in the attached PDF. Original documents submitted to finance.',
            ],
        ], ['receipt' => 'travel-receipts-october.pdf']);
        $this->approveCurrent($approvals, $approved, 'Budget line confirmed. Approved.');
        $this->approveCurrent($approvals, $approved, 'Cost centre has sufficient balance.');
        $this->approveCurrent($approvals, $approved, 'Processed for payment in the next finance cycle.');
        $this->backdate($approved, 21);

        /* 2. Reimbursement rejected at finance. */
        $rejected = $this->createRequest($service, $attachments, $ferdinand, $created['REIMB'], [
            'title' => 'Team dinner reimbursement — project close-out',
            'description' => 'Close-out dinner for the HR systems project team of six people.',
            'priority' => Priority::Low,
            'form_data' => [
                'expense_category' => 'Client entertainment',
                'amount' => '3850000',
                'expense_date' => now()->subDays(12)->toDateString(),
                'cost_centre' => 'HR-PRJ-07',
                'notes' => 'Itemised bill attached.',
            ],
        ], ['receipt' => 'team-dinner-invoice.pdf']);
        $this->approveCurrent($approvals, $rejected, 'Team milestone acknowledged.');
        $this->rejectCurrent($approvals, $rejected, 'The claim exceeds the entertainment limit for a team of six and no client attendees are listed. Please resubmit as a staff welfare expense with the correct cost centre.');
        $this->backdate($rejected, 10);

        /* 3. Reimbursement returned for revision. */
        $revision = $this->createRequest($service, $attachments, $ferdinand, $created['REIMB'], [
            'title' => 'Home office equipment reimbursement',
            'description' => 'Ergonomic chair and monitor riser purchased for remote working days.',
            'priority' => Priority::Normal,
            'form_data' => [
                'expense_category' => 'Office supplies',
                'amount' => '1980000',
                'expense_date' => now()->subDays(7)->toDateString(),
                'cost_centre' => 'HR-OPS-24',
                'notes' => 'Purchased from the approved supplier catalogue.',
            ],
        ], ['receipt' => 'chair-payment-receipt.pdf']);
        $this->revisionCurrent($approvals, $revision, 'Please attach the tax invoice; the current attachment is the payment receipt only. Also confirm the asset will be registered with IT.');
        $this->backdate($revision, 6);

        /* 4. Leave request waiting on the department manager. */
        $leave = $this->createRequest($service, $attachments, $ferdinand, $created['LEAVE'], [
            'title' => 'Annual leave — 8 to 12 December',
            'description' => 'Five working days of annual leave. Handover agreed with Nadia for the finance reconciliation.',
            'priority' => Priority::Normal,
            'form_data' => [
                'leave_type' => 'Annual leave',
                'start_date' => now()->addDays(24)->toDateString(),
                'end_date' => now()->addDays(28)->toDateString(),
                'days' => '5',
                'handover' => 'Nadia Prameswari',
                'reason' => 'Family holiday, booked outside the quarter-end close.',
            ],
        ]);
        $this->backdate($leave, 2);
        $this->agePendingTask($leave, 6);

        /* 5. IT access request pending at the IT manager, overdue. */
        $it = $this->createRequest($service, $attachments, $ferdinand, $created['ITACC'], [
            'title' => 'Read access to the finance reporting workspace',
            'description' => 'Monthly headcount reconciliation needs read-only access to the finance reporting workspace.',
            'priority' => Priority::High,
            'form_data' => [
                'system_name' => 'Finance Reporting Workspace',
                'access_type' => 'Additional permission',
                'business_need' => 'Preparing the monthly people-cost reconciliation for the finance controller.',
                'needed_by' => now()->addDays(3)->toDateString(),
                'acknowledgement' => ['I accept the company acceptable use policy'],
            ],
        ]);
        $this->approveCurrent($approvals, $it, 'Required for the monthly reconciliation. Approved.');
        $this->backdate($it, 4);
        $this->agePendingTask($it, 30, overdue: true);

        /* 6. Purchase request waiting on finance, raised by IT. */
        $purchase = $this->createRequest($service, $attachments, $rina, $created['PURCH'], [
            'title' => 'Replacement network switches for the second floor',
            'description' => 'Two managed switches to replace end-of-life units that are causing intermittent packet loss.',
            'priority' => Priority::Urgent,
            'form_data' => [
                'vendor' => 'PT Sinar Teknologi',
                'amount' => '48500000',
                'budget_line' => 'IT-CAPEX-26',
                'required_by' => now()->addDays(10)->toDateString(),
                'specification' => '2 × 48-port managed gigabit switches, redundant power supplies, 3-year support.',
            ],
        ], ['quotation' => 'switch-quotation-sinar.pdf']);
        $this->approveCurrent($approvals, $purchase, 'Confirmed the failure pattern; replacement is justified.');
        $this->approveCurrent($approvals, $purchase, 'Sourcing completed against the framework agreement.');
        $this->backdate($purchase, 3);
        $this->agePendingTask($purchase, 18);
    }

    /**
     * @param  array{title: string, description: string, priority: Priority, form_data: array<string, mixed>}  $payload
     * @param  array<string, string>  $documents  field key => demo filename
     */
    private function createRequest(
        RequestService $service,
        AttachmentService $attachments,
        User $requester,
        RequestType $type,
        array $payload,
        array $documents = [],
    ): ServiceRequest {
        $request = $service->save($requester, $type, [
            'title' => $payload['title'],
            'description' => $payload['description'],
            'priority' => $payload['priority']->value,
            'form_data' => $payload['form_data'],
        ]);

        foreach ($documents as $fieldKey => $filename) {
            $attachments->store($this->demoDocument($filename), $request, $requester, $fieldKey);
        }

        return $service->submit($request, $requester)->refresh();
    }

    private function demoDocument(string $filename): UploadedFile
    {
        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    private function approveCurrent(ApprovalService $approvals, ServiceRequest $request, string $comment): void
    {
        $task = $this->pendingTask($request);
        $approvals->approve($task, $task->approver, $comment);
    }

    private function rejectCurrent(ApprovalService $approvals, ServiceRequest $request, string $comment): void
    {
        $task = $this->pendingTask($request);
        $approvals->reject($task, $task->approver, $comment);
    }

    private function revisionCurrent(ApprovalService $approvals, ServiceRequest $request, string $comment): void
    {
        $task = $this->pendingTask($request);
        $approvals->requestRevision($task, $task->approver, $comment);
    }

    private function pendingTask(ServiceRequest $request): ApprovalTask
    {
        $task = $request->workflowInstance->tasks()->where('status', 'pending')->firstOrFail();

        return $task->load('stage', 'approver', 'workflowInstance.request');
    }

    private function backdate(ServiceRequest $request, int $days): void
    {
        $date = now()->subDays($days);

        $request->forceFill([
            'submitted_at' => $date,
            'created_at' => $date,
            'updated_at' => $date->copy()->addHours(6),
        ])->saveQuietly();

        foreach ($request->workflowInstance->tasks as $index => $task) {
            $task->forceFill([
                'started_at' => $date->copy()->addHours(2 + ($index * 5)),
                'completed_at' => $task->completed_at ? $date->copy()->addHours(5 + ($index * 5)) : null,
                'created_at' => $date->copy()->addHours(1 + ($index * 5)),
            ])->saveQuietly();
        }
    }

    private function agePendingTask(ServiceRequest $request, int $hoursAgo, bool $overdue = false): void
    {
        $task = $request->workflowInstance->tasks()->where('status', 'pending')->first();

        if (! $task) {
            return;
        }

        $started = now()->subHours($hoursAgo);
        $task->forceFill([
            'started_at' => $started,
            'due_at' => $overdue ? $started->copy()->addHours(4) : $started->copy()->addHours(24),
        ])->saveQuietly();
    }
}
