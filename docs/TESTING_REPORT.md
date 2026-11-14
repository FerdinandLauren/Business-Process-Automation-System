# Testing Report — Arunika Flow

## 1. How to run the suite

```bash
php artisan test
```

The suite runs against an **in-memory SQLite database** configured in `phpunit.xml`, so it never reads or writes the demo database. Each test class seeds the reference data it needs with `$this->seed()`.

Individual areas:

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --filter ReportTest
```

## 2. Latest result

```
Tests:    103 passed (339 assertions)
Duration: 119.11s
```

Environment: PHP 8.3.16, Laravel 12, Livewire 3.8.3, SQLite `:memory:`, timezone `Asia/Jakarta`.

No test is skipped and no test is failing.

## 3. Coverage by area

| File | Tests | What it proves |
| --- | --- | --- |
| `Unit/SLAServiceTest` | 11 | Business-hour addition across lunch-less working days and weekends, zero-hour edge case, minute carry-over, business-minute counting, never-negative results, due-soon/overdue/on-track classification, humanised remaining time |
| `Unit/FormDataValidatorTest` | 7 | Schema-driven rules for text, numeric, date, dropdown, checkbox and file fields; required enforcement; draft mode; rejection of values outside the schema; stripping of unknown keys |
| `Feature/AuthenticationTest` | 8 | Login screen, valid and invalid credentials, inactive account blocking, automatic sign-out of deactivated users, logout, hashed passwords, login throttling |
| `Feature/AuthorizationTest` | 10 | Guests redirected; employee blocked from admin and reports; manager can approve but not administer; finance and management can read reports; system administrator reaches every admin section; process administrator reaches workflows but not users; cross-user request access denied; approver can read the request they decide; only the requester can edit a draft |
| `Feature/AdminTest` | 6 | Last system administrator cannot be demoted; administrator cannot deactivate themselves; the system-administrator permission column is untouchable; role changes are audited; user creation writes an audit record and hashes the password; audit trail filtering |
| `Feature/RequestWorkflowTest` | 9 | Partial draft save; submission starts the workflow and assigns the right approver with a due date; missing fields and missing required attachments block submission; double submission refused; non-requester submission refused; cancellation cancels pending tasks; revision resubmission resumes the same stage; closed requests cannot be cancelled |
| `Feature/ApprovalDecisionTest` | 8 | Approval advances the stage and notifies the requester; final approval completes the request; only the assigned approver may decide; a task cannot be decided twice; rejection needs a reason; rejection terminates and notifies; revision returns the request to the requester; inactive approvers cannot decide |
| `Feature/SlaMonitorTest` | 5 | One breach recorded with two notifications and one audit entry; a second run is idempotent; a task in the reminder window gets exactly one reminder; `--dry-run` changes nothing; decided tasks are ignored |
| `Feature/ReportTest` | 13 | Reports require the permission; all five tabs render; the status filter switches between request and task statuses; department filtering; inverted date range rejected; honest empty state; UTF-8 CSV with BOM and correct header and row count; drafts excluded; formula injection neutralised; SLA export is numeric; export respects filters; unknown report key rejected |
| `Feature/DashboardTest` | 4 | Employee figures are scoped to their own requests; approver figures match assigned tasks; administrator sees company-wide figures and recent activity; the status distribution reconciles with the database |
| `Feature/AttachmentTest` | 6 | Requester downloads with `nosniff`; unrelated employee refused; assigned approver allowed; an attachment cannot be fetched through a different request; disallowed extensions rejected before storage; only the requester can remove a draft attachment |
| `Feature/Livewire/RequestFormComponentTest` | 6 | Submitting through the component starts the workflow; saving a draft does not; required-field errors appear on the mapped form fields; a missing required attachment appears on the file field; the draft survives a failed submission and the alert says so; users without `request.create` are refused |
| `Feature/Livewire/ApprovalDetailComponentTest` | 5 | Approval through the component advances the stage; a rejection without a reason is refused; a rejection closes the request; a non-approver cannot open the decision screen; history records the actor, action and comment |
| `Feature/Livewire/WorkflowEditorTest` | 5 | Editor closed without `workflow.manage`; publishing supersedes the previous version and audits it; a published version cannot be edited; cloning creates an inactive draft; a named-user stage requires a person |

## 4. Manual verification performed

Beyond the automated suite, the running application was exercised in a browser:

- Every page in the navigation for each role renders without console errors: overview, request register, request detail, request centre, notifications, settings, administration hub, users, roles, departments, request types, form editor, audit trail, workflow list, workflow editor, reports (all five tabs).
- All 49 application routes respond as expected, including 403 for intentionally forbidden combinations (for example a system administrator opening the approval inbox, which requires `approval.process`).
- Submitting an incomplete reimbursement in the browser shows the alert and field-level errors, and keeps the draft.
- A recorded SLA breach appears on the approval detail with its timestamp.

## 5. Defects found and fixed during verification

| Defect | Symptom | Fix |
| --- | --- | --- |
| Blade `@php` block pairing | Approval detail returned 500 (`unexpected token "endif"`) because an inline `@php(...)` before a `@php … @endphp` block swallowed the template | Converted inline calls in affected templates to block form |
| SLA due-soon classification | Every future deadline was reported as due soon | Measured the time remaining until the deadline instead of a reversed difference |
| Request-form error mapping | A failed submission showed no message next to the offending fields | Mapped service error keys to `formData.*` and `fieldFiles.*` |
| Injected empty model | An optional model parameter could arrive as an empty instance in Livewire components, triggering an authorisation failure | Normalised non-existent models to `null` in the request form, workflow editor and form editor |
| Misleading alert | "The request could not be saved" despite the draft being stored | Copy now states that the draft was saved and fields need attention |
| Report export type | `TypeError` on CSV download (Symfony response returned from an `Illuminate` type hint) | Corrected the return type import |

## 6. What is not covered by automated tests

- Browser rendering, responsiveness and visual design: verified manually, not asserted.
- PostgreSQL: the suite runs on SQLite. Migrations avoid engine-specific features, but no PostgreSQL run was performed.
- Real concurrent HTTP requests: the double-decision guard is tested by deciding the same task twice sequentially. True parallel execution was not simulated.
- Email and queued work: no mail transport or queue worker is used in the MVP.
- Timezone transitions and public holidays: the business calendar has no holiday table.

## 7. Reproducing the demo state

```bash
php artisan migrate:fresh --seed
php artisan serve
npm run build
```

The seeder creates seven departments, eleven users, four request types, four published form versions, four published workflows and six requests covering approved, rejected, revision-required, pending-at-first-stage and overdue-at-second-stage paths, plus one recorded SLA breach.
