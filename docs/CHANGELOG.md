# Changelog — Arunika Flow

The project was built in milestones, each committed and pushed separately. Dates reflect the development period.

## Phase 0 — Specification

- Added `PROJECT.md` (single source of truth) and `docs/IMPLEMENTATION_PLAN.md` (milestones M0–M7, architecture, risks, test cases).

## M1 — Foundation

- Bootstrapped Laravel 12 with SQLite, `Asia/Jakarta`, Vite and self-hosted IBM Plex fonts.
- Added `config/bpa.php` for company identity, attachment limits and the SLA business calendar.
- Installed Livewire 3.8.3 after reviewing the security advisories affecting earlier 3.x releases.

## M2 — Data model

- Migrations for departments, roles, permissions, pivots, audit logs, request types, form templates, request numbering, requests, attachments, workflow templates, stages, instances, tasks, histories and events.
- Enums for request status, task status, instance status, priority, approver scope.
- Models with relationships, casts and state helpers.

## M3 — Services and engine

- `WorkflowEngine` (start, advance, complete, terminate, revision, resume, approver resolution).
- `ApprovalService` with transactional, lock-protected decisions.
- `RequestService` for drafts, submission, resubmission and cancellation.
- `NotificationService`, `AttachmentService`, `SLAService`, `AuditLogger`.
- Policies and gates for request, approval and administration access.

## M4 — Design system and shell

- "Industrial Ledger" tokens: warm paper, ink, cobalt accent, hairline borders, dot status markers, mono numerics.
- Application shell with dark navigation rail, guest login screen and shared components.

## M5 — Authentication and access control

- Login with rate limiting, inactive-account blocking, logout, forgot/reset password, account settings.
- `EnsureUserIsActive` middleware that signs out deactivated users.

## M6 — Request module

- Request centre, register, detail page, dynamic schema-driven form, draft/submit, attachments, cancellation.
- Role-aware dashboard and notification centre.

## M7 — Approvals

- Approval inbox with filters and overdue highlighting.
- Approval detail with decision context, form data, history, timeline and confirm dialogs.

## M8 — Workflow management

- Workflow list, editor, stage ordering, approver configuration, publish and version cloning.

## M9 — Administration

- Administration hub, user management with last-administrator protection, audit trail with filters, department CRUD, request-type catalogue and versioned form builder, editable role/permission matrix.

## M10 — SLA

- `reminder_sent_at` / `breached_at` claim columns, `bpa:sla-monitor` command with `--dry-run`, scheduler entry, breach label on the approval detail.

## M11 — Reporting

- Five operational reports with shared filters, stat cards and streamed CSV export with formula-injection sanitising.

## M12 — Verification and hardening

- Added an in-memory SQLite test configuration so the suite never touches the demo database.
- 103 automated tests across unit and feature layers, including Livewire component tests for the request form, approval decision and workflow editor.
- Fixed: SLA due-soon classification measured time in the wrong direction.
- Fixed: a Blade `@php` block pairing bug that broke the approval detail template.
- Fixed: request-form validation errors were invisible because service error keys were not mapped to the form's `formData.*` / `fieldFiles.*` bindings.
- Fixed: an injected empty model was treated as a bound request in the Livewire editors.
- Fixed: a failed submission now correctly says the draft was saved.
- Documentation set: business analysis, architecture, database design, workflow specification, testing report, user guide, changelog and README.
