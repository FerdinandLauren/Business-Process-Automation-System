# System Architecture — Arunika Flow

## 1. Stack

| Layer | Choice | Version |
| --- | --- | --- |
| Language | PHP | 8.3.16 |
| Framework | Laravel | 12.x |
| Interactive UI | Livewire | 3.8.3 |
| Styling | Tailwind CSS | 4.x (Vite plugin) |
| Build | Vite | 6.x |
| Fonts | IBM Plex Sans / IBM Plex Mono, self-hosted via `@fontsource` | 5.x |
| Database (local + tests) | SQLite | file for development, in-memory for the suite |
| Database (target) | PostgreSQL 16 | migrations written to be portable |
| Tests | PHPUnit | Laravel's bundled runner |

## 2. Layering

```
routes/            HTTP entry points (controllers, Livewire components, scheduler)
  ↓
app/Http/          Thin controllers: authorise, validate, delegate
app/Livewire/      Interactive components: forms, inbox, editors, notification centre
  ↓
app/Services/      Business logic — the only place state transitions happen
  ↓
app/Models/        Eloquent models, relationships, small state helpers
database/          Migrations, seeders
app/Policies/      Authorisation rules per model
app/Support/       Schema-driven form validation, request-number generation
```

Design rule: **no business logic in the UI layer.** A Livewire component collects input, calls a service and renders the result. State transitions (submit, decide, cancel, publish) exist once, inside a service, and are reused by every caller.

## 3. Services

| Service | Responsibility |
| --- | --- |
| `RequestService` | Create/update drafts, submit, resubmit after revision, cancel. Enforces ownership and completeness. |
| `WorkflowEngine` | Start an instance, resolve the approver for a stage, advance, complete, terminate, mark revision, resume. |
| `ApprovalService` | Apply a decision atomically: lock the task, verify authority and state, write history, then delegate the transition. |
| `NotificationService` | Create in-app notifications for assignee, requester and SLA events. |
| `SLAService` | Business-hour arithmetic, due-date calculation, state classification, human-readable remaining time. |
| `AttachmentService` | Validate, store, delete and locate uploaded files; audit each change. |
| `AuditLogger` | Append-only audit records with actor, action, entity, metadata and request context. |
| `ReportService` | Five report payloads, filters, CSV headers and streamed rows. |
| `MonitorSlaCommand` (`bpa:sla-monitor`) | Scheduled reminders and breach recording, idempotent via claim columns. |

## 4. Request lifecycle

```
draft ──submit──▶ under_review ──approve (not last stage)──▶ under_review (next stage)
  ▲                   │                                          │
  │                   ├──approve (last stage)──▶ approved ───────┘
  │                   ├──reject────────────────▶ rejected
  └──resubmit─────────┴──request revision─────▶ revision_required
                      └──cancel───────────────▶ cancelled
```

Every transition runs inside a database transaction. The approval decision additionally:

1. Opens a transaction and selects the task `lockForUpdate()`.
2. Verifies the actor is the assigned approver and the task is still pending.
3. Verifies the request is `under_review` and the instance is running.
4. Performs a **conditional update** (`where status = pending`) and asserts exactly one row changed.
5. Writes an `approval_histories` row, then advances, completes or terminates the instance.

Step 4 is the concurrency guard: a second decision arriving after the first has committed changes zero rows and is rejected with a `WorkflowException`, so a task cannot be decided twice even if two requests race.

## 5. Workflow versioning

- `workflow_templates` holds versions per request type, with `published_at`, `is_active` and `superseded_at`.
- Publishing a version deactivates and supersedes the previous active version for that type.
- A published version, or any version that already has instances, is **locked** for editing; the UI offers "create new version" instead.
- `workflow_instances` pins `workflow_template_id` and `workflow_version`, so editing configuration never rewrites history.
- Form templates follow the same model with `form_templates.version` and `published_at`.

## 6. Authorisation

Two layers, both server-side:

- **Gates** for coarse capability: `access-admin`, `manage-users`, `manage-organisation`, `manage-workflows`, `view-audit-logs`, `view-reports`, `process-approvals`. Routes are grouped behind them, so an unauthorised request never reaches a controller.
- **Policies** for record-level rules: `ServiceRequestPolicy` (view/update/submit/cancel/download), `ApprovalTaskPolicy` (view/decide), `AdminPolicy`.

Permissions are data, not code: seven roles map to eight permissions through `permission_role`, editable in the administration UI. The `system_admin` column is intentionally locked so an administrator cannot remove their own ability to administer the system, and any edit that would leave no active user with `user.manage` is rejected.

## 7. Reporting

`ReportService` exposes one filter set (period, department, request type, status) and five reports:

| Report | Basis | Measures |
| --- | --- | --- |
| Request summary | `requests.submitted_at` | Volume, outcome, open count, average cycle in business hours |
| Approval summary | `approval_tasks.started_at` | Tasks reaching an approver, decided, pending, average decision time |
| SLA performance | `approval_tasks.started_at` | On-time, late, open and compliance per stage |
| Department activity | `requests.submitted_at` | Raised, approved, rejected, open, average cycle per department |
| Status distribution | `requests.submitted_at` | Current status mix with share and bar |

Drafts are excluded (they have no `submitted_at`). Cycle time uses business minutes, not wall-clock time. Exports stream in chunks with a UTF-8 BOM and prefix any cell that begins with `=`, `+`, `-`, `@`, tab or carriage return with an apostrophe, which neutralises spreadsheet formula injection without altering ordinary values.

## 8. SLA monitoring

`routes/console.php` schedules `bpa:sla-monitor` every fifteen minutes with `withoutOverlapping()` and `onOneServer()`. The command:

- Claims due-soon tasks (`due_at` within the reminder window) by setting `reminder_sent_at`, then notifies the approver.
- Claims breached tasks (`due_at` in the past) by setting `breached_at`, then writes an audit record and notifies the approver and requester.
- Supports `--dry-run`, which prints what would happen and changes nothing.

Both paths claim through a conditional update, so repeated runs are idempotent — verified by tests.

## 9. Frontend and design system

- A single application shell (`components.layouts.app`) with a dark navigation rail, content canvas and footer.
- Design tokens live in `resources/css/app.css`: warm paper background, ink text, cobalt accent, hairline borders, 2–3px radii, dot status markers, no gradients or glass effects.
- Shared components: `panel`, `page-header`, `stat`, `status`, `empty-state`, `pagination`, `data-table`.
- Fonts are self-hosted, so the app renders identically without network access.
- `public/build` is generated by `npm run build` and is intentionally git-ignored.

## 10. Configuration

`config/bpa.php` centralises company identity, timezone, attachment limits and the SLA business calendar:

```php
'attachments' => ['max_kb' => 5120, 'extensions' => ['pdf', 'jpg', ...]],
'sla' => ['workday_start' => '08:00', 'workday_end' => '17:00', 'workdays' => [1,2,3,4,5], 'reminder_hours_before' => 4],
```

Environment overrides: `APP_TIMEZONE`, `BPA_TIMEZONE`, `BPA_ATTACHMENT_DISK`, `BPA_ATTACHMENT_MAX_KB`, `BPA_SLA_REMINDER_HOURS`.

## 11. Known architectural limits

- SQLite does not implement `SELECT … FOR UPDATE`; the conditional-update guard is what actually protects decisions, and it is exercised by tests. PostgreSQL will additionally take real row locks.
- No queue worker is required: notifications are written synchronously inside the same transaction as the state change.
- The SLA calendar has no public-holiday awareness.
