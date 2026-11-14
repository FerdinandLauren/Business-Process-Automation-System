# Technical Implementation Plan

**Project:** Business Process Automation System (PT Arunika Industrial Solutions — fictional demo company)
**Specification:** `PROJECT.md` v1.0.0 (single source of truth)
**Plan status:** Phase 0 deliverable

---

## 1. Repository Assessment (Phase 0)

| Item | Finding |
|---|---|
| Repository contents at start | `PROJECT.md` only. No application code, no dependency manifest, no CI. |
| Git remote | `github.com/FerdinandLauren/Business-Process-Automation-System` (empty repository) |
| Local runtime | PHP 8.3.16 (Laragon), Composer, Node 24, npm 11, Git |
| Database server available locally | No PostgreSQL server or Docker on the build machine. MySQL 8 and SQLite are available. |
| Reusable components | None. Greenfield implementation. |

### Environment decision

The specification recommends PostgreSQL. The build machine has no PostgreSQL server and no Docker,
so a production-shaped local run on PostgreSQL is not verifiable in this environment.

Decision:

- **Local development and the automated test suite use SQLite** (file based, zero configuration, fast tests).
- **Migrations and queries are written to be PostgreSQL-compatible** (no SQLite-only SQL, `json` columns,
  explicit indexes, no `enum` DB type — string + application-level validation).
- A `docker-compose.yml` and documented `.env` values are provided so the same codebase runs on
  PostgreSQL 16 without changes. This is documented as a limitation, not claimed as tested.
- Evidence of real execution is produced on the stack that could actually run here: SQLite. No claim of
  PostgreSQL verification is made in the README.

---

## 2. Technology Decisions

| Layer | Choice | Reason |
|---|---|---|
| Framework | Laravel 12 (PHP 8.3) | Mandated by spec; modular monolith. |
| UI | Blade + Livewire 3 + Alpine.js | Mandated; Livewire used where stateful interaction is real (approval actions, dynamic form, workflow stage builder, notification bell). |
| Styling | Tailwind CSS 4 + custom design tokens | Utility CSS with a project-specific token layer, not stock utility soup. |
| Auth | Laravel session auth (custom controllers) | Full control over the design language; no Breeze/Jetstream default UI. |
| Authorization | Policies + Gate + role/permission resolver | Server-side checks on every resource. |
| RBAC | `roles`, `permissions`, `role_user`, `permission_role` | Spec entity model; permissions are checked in policies/services. |
| Tests | PHPUnit + Laravel feature/unit tests | Ships with Laravel 12; spec allows PHPUnit or Pest. |
| Queue | Database driver (configured) | In-app notifications are written transactionally; queued email is out of MVP. |
| Files | Private local disk (`storage/app/private`) | Uploads never web-served directly; authorized download route only. |

---

## 3. Domain Model Extensions to the Spec

The spec's table list is followed. Additions are marked and justified:

| Addition | Justification |
|---|---|
| `workflow_stages.approver_scope` (`requester_department` \| `global` \| `specific_user`) and `specific_user_id` | "Department Manager" means the manager *of the requester's department*; "Finance Manager" is global. Without scope the demo scenario cannot resolve approvers. |
| `workflow_templates.published_at`, `superseded_at` | Immutable versioning: editing a published workflow clones a new version instead of mutating history. |
| `form_templates.published_at` | Same reason for forms. |
| `requests.current_stage_order` (denormalized) | Fast inbox/tracking queries; derived from the active workflow instance. |
| `approval_tasks.started_at` | SLA clock start per stage. |
| `notifications.url` | Deep-link target for the in-app notification. |
| `audit_logs.user_agent` | Optional forensic context alongside `ip_address`. |

No entity in the spec is removed.

---

## 4. Architecture

```
app/
  Actions/               # single-purpose write operations (create request, submit, decide…)
  Enums/                 # RequestStatus, TaskStatus, Priority, ApproverScope
  Services/
    RequestService.php
    WorkflowEngine.php
    ApprovalService.php
    NotificationService.php
    SLAService.php
    AuditLogger.php
    ReportingService.php
  Policies/
  Livewire/              # stateful UI components
  Http/Controllers/      # thin, delegate to services
  Http/Middleware/       # EnsureUserIsActive
  Models/
  Support/RequestNumberGenerator.php
```

Business rules live in `Services`/`Actions`. Controllers and Livewire components never mutate workflow state
directly. Approval decisions run inside a DB transaction with an atomic conditional update on the task row
(`where status = pending`) plus `lockForUpdate` on supporting rows; the affected-row count is the double-decision
guard (BR-08) and works on both SQLite and PostgreSQL.

### Automated flow on submission

1. Validate dynamic form data against the active form template schema (server-side).
2. Generate request number: `REQ-{YYYYMM}-{seq}` inside a transaction with a unique constraint.
3. Resolve the active workflow template for the request type (pinned by version).
4. Create workflow instance (pins `workflow_template_id` + `workflow_version`).
5. Resolve stage-1 approver by scope + role; fail safely if none.
6. Create approval task with `due_at` computed by `SLAService` over the business calendar.
7. Write audit log + notifications, emit workflow event.
8. Commit.

Approval decisions follow the same shape: authorize → lock → re-check pending → record history → advance, revise,
or terminate → notify → audit → commit.

---

## 5. Milestones

Each milestone is a working, testable slice committed separately.

### M0 — Foundation & plan
- Git repository initialisation, Laravel 12 scaffold, `.env.example`, SQLite local config.
- Design tokens, typography, app shell (sidebar/top bar), auth screens.
- Phase 0 documents: repository assessment, plan, dependency analysis.
- **Exit:** app boots, login page rendered, tests run.

### M1 — Identity, RBAC, organisation, audit
- Migrations: departments, users, roles, permissions, pivots, audit_logs.
- Session auth with login throttling, active-account enforcement, password reset (log mailer locally).
- Policies + Gate, role management, department CRUD, user CRUD.
- Audit logger wired to auth and admin actions.
- **Exit:** `AuthenticationTest`, `AuthorizationTest`, `AdminTest` pass; seeded demo accounts.

### M2 — Request management
- Request types, versioned form templates, form builder (admin).
- Request Center flow: type selection → dynamic form → attachments → draft/submit.
- My Requests: filters, detail, timeline, cancel, revision resubmission.
- Private attachment storage + authorized download.
- **Exit:** dynamic validation, draft isolation, attachment authorization.

### M3 — Workflow engine (critical milestone)
- Versioned workflow templates + stage builder (role, scope, SLA hours, ordering).
- Instance creation, sequential tasks, approve/reject/revision, cancellation, completion.
- Approval Inbox + detail with attachments and decision history.
- Transactional guarantees and double-decision prevention.
- **Exit:** `RequestWorkflowTest`, `ApprovalDecisionTest`, `AttachmentTest`, `Livewire/RequestFormComponentTest`, `Livewire/ApprovalDetailComponentTest`, `Livewire/WorkflowEditorTest` pass.

### M4 — Notifications & SLA
- Notification center + bell, unread counts, read actions.
- Triggers on submit/assign/approve/reject/revision/complete.
- Business-calendar SLA calculator, due dates, overdue detection, reminder command + scheduler.
- **Exit:** `SlaMonitorTest` and `SLAServiceTest` pass; SLA unit tests cover weekends and working hours.

### M5 — Dashboards & reporting
- Role-aware overview (employee, approver, admin/process admin, management) on live queries.
- Reports: request summary, approval summary, SLA performance, department activity, status distribution.
- Filters and streamed CSV export. No static or fabricated numbers.
- **Exit:** `ReportTest` and `DashboardTest` pass; dashboard figures reconcile with the database.

### M6 — Security & QA
- Authorization sweep, upload hardening, invalid state transitions, concurrency test, notification/SLA tests.
- Full test suite green; document results in `docs/TESTING_REPORT.md`.

### M7 — Documentation & demo
- README (English, per spec §16), business/workflow/database/architecture docs, user guide, changelog.
- Demo seeder with the Ferdinand → Andi → Budi → Sari scenario plus alternate paths.
- Final report: implemented features, test evidence, limitations, next steps.

---

## 6. Design Direction

**Concept: Industrial Ledger.** PT Arunika is an industrial solutions company; the product should read like
a well-kept operations ledger rather than a SaaS template.

- Warm paper neutrals (`#F7F6F3` canvas, hairline `#E3E1DC`), ink `#17181A`, near-black navigation rail.
- One restrained cobalt accent for primary actions, active navigation and focus; semantic colours are muted
  (deep green / amber / brick), never a rainbow badge system.
- IBM Plex Sans (400/500/600) + IBM Plex Mono for request numbers, timestamps, SLA clocks and figures.
  Self-hosted via npm — no runtime CDN dependency.
- 3px corner radius, 1px hairlines, no decorative gradients, no glass, no stock illustration.
- Status is a dot + uppercase micro-label. Workflow progress is a numbered vertical rail, not a progress bar.
- Motion is 120–160 ms, ease-out, state feedback only; `prefers-reduced-motion` respected.
- Dense tables with tabular numerals; empty states that state the next action; forms with inline field errors.

Design quality is verified by running the app and looking at rendered pages at desktop and mobile widths,
not by asserting it.

---

## 7. Risks & Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| No PostgreSQL locally | Spec target unverified | Portable migrations, `docker-compose.yml`, documented limitation. |
| Workflow engine concurrency | Duplicate approvals / inconsistent state | Transaction + row lock + conditional update + affected-row assertion; concurrency feature test. |
| Versioning regressions | Running instances change behaviour | Instances pin template id/version; editing clones a version. |
| Scope creep from V2 features | MVP not finished | V2 items explicitly excluded and listed as limitations. |
| Spaces in project path | Tooling quirks on Windows | Verify `artisan`, Vite build and tests from the real path. |
| Demo data looking fabricated | Portfolio credibility | All figures derive from seeded transactions; seeder is the only source of demo numbers. |
