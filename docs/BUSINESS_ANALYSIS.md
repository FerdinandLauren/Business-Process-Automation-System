# Business Analysis — Arunika Flow

Business Process Automation System for **PT Arunika Industrial Solutions** (fictional). This document records the business problem, the actors, the agreed scope and how each requirement is realised in the application.

---

## 1. Business problem

Administrative requests at Arunika (reimbursements, leave, IT access, purchases) were handled over email and chat. The consequences were measurable:

- **No single record.** A request existed as a thread, a spreadsheet row and a verbal agreement. Nobody could state the current status with confidence.
- **Approval authority was implicit.** "Ask Andi first, then finance" lived in people's heads. When someone was on leave, requests stalled silently.
- **No accountability trail.** Decisions were not timestamped or attributed, so disputes could not be resolved from evidence.
- **No operational visibility.** Management could not answer "how many requests are open", "who is the bottleneck" or "what share of approvals met their deadline" without manual counting.
- **Deadlines were unenforceable.** Nothing tracked how long a request had been waiting, so slow approvals were invisible.

## 2. Root cause

The organisation had a well-understood approval process but no system of record. The process logic was **social**, not **encoded**: routing rules, approver authority and deadlines existed only as convention. Any solution therefore had to encode routing and deadlines as data, not as documentation.

## 3. Objectives

1. Give every administrative request a single authoritative record with a status that reflects reality.
2. Encode the approval route, the approver for each stage and the stage deadline as configurable data.
3. Record every decision with actor, timestamp and comment in an append-only trail.
4. Compute operational reporting from live records rather than maintained spreadsheets.
5. Enforce authorisation on the server, so the UI cannot be used to bypass the process.

## 4. Actors

| Actor | Description | Primary need |
| --- | --- | --- |
| Employee | Any staff member raising a request | Submit quickly, see status, respond to revision requests |
| Department Manager | Approves requests raised in their department | A queue of decisions with context and deadlines |
| Finance Manager / Administrator | Approves and processes financial requests | Confidence that cost-centre and budget context is present |
| Process Administrator | Owns request types, forms and approval routes | Change routing without developer involvement |
| System Administrator | Owns accounts, roles, departments and the audit trail | Control access, prove accountability |
| Management | Needs aggregate performance data | Volume, cycle time and SLA compliance by department and stage |

## 5. Scope (MVP)

### In scope

- Four request types: reimbursement, leave, IT access and purchase.
- Versioned, configurable forms per request type.
- Versioned, configurable sequential approval workflows with per-stage deadlines.
- Draft, submit, approve, reject, request-revision, resubmit and cancel.
- Attachments with type, size and access control.
- Role- and permission-based access control, with an editable permission matrix.
- Operational dashboard per role, and five operational reports with CSV export.
- In-app notifications and an append-only audit trail.
- SLA reminders and breach recording on a schedule.

### Out of scope (documented as future work)

- Parallel or conditional approval branches (the engine is strictly sequential).
- SLA escalation to a second approver when a deadline passes.
- Email or chat delivery of notifications (in-app only).
- SSO, multi-company tenancy and mobile applications.
- Automatic payment or procurement execution against external systems.

## 6. Business rules as implemented

| Rule | Statement | Where it lives |
| --- | --- | --- |
| BR-01 | Only the requester may edit, submit or cancel their own request. | `ServiceRequestPolicy`, `RequestService::assertEditable` |
| BR-02 | Only the user assigned to the active stage task may decide it. | `ApprovalTaskPolicy::decide`, `ApprovalService::decide` |
| BR-03 | Approval is sequential; the next stage opens only after the previous one is approved. | `WorkflowEngine::advance` |
| BR-04 | A rejection closes the request permanently; the requester must raise a new one. | `WorkflowEngine::terminate` |
| BR-05 | A revision request returns the request to the requester and resumes at the same stage. | `WorkflowEngine::markRevisionRequired`, `resumeAfterRevision` |
| BR-06 | Published workflows and forms are immutable; a change creates a new version. | `WorkflowEditor`, `FormTemplateEditor` |
| BR-07 | A request pins the workflow version it started with, so later changes do not alter in-flight requests. | `workflow_instances.workflow_template_id` + `workflow_version` |
| BR-08 | Every decision and configuration change is written to the audit trail. | `AuditLogger` |
| BR-09 | At least one active system administrator must always remain. | `UserController::assertAdminCoverage` |
| BR-10 | A user's own account status and last role cannot be removed by themselves. | `UserController::toggleStatus`, `update` |
| BR-11 | Drafts validate field types but not completeness, so work can be saved and resumed. | `FormDataValidator::validate(enforceRequired: false)` |
| BR-12 | Only complete requests with their required attachments can be submitted. | `RequestService::submit` |

## 7. Requirement traceability

| Spec area | Implementation |
| --- | --- |
| FR-01…FR-05 request lifecycle | `RequestService`, `WorkflowEngine`, request pages |
| FR-06 dynamic forms | `FormTemplate` schema + `FormDataValidator` + `RequestForm` component |
| FR-07 attachments | `AttachmentService`, `request_attachments`, download route |
| FR-08 RBAC | `roles`, `permissions`, policies and gates, permission matrix UI |
| FR-09 approval workflow | `WorkflowEngine`, `ApprovalService`, approval inbox and detail |
| FR-10 notifications | `NotificationService`, notification centre with unread badge |
| FR-11 SLA | `SLAService`, `bpa:sla-monitor` scheduled command, breach labels |
| FR-12 audit trail | `AuditLogger`, `admin/audit` with filters |
| FR-13 dashboard | `DashboardController`, role-aware figures computed from live data |
| FR-14 reporting | `ReportService`, `ReportController`, five reports with CSV export |
| FR-15 administration | Departments, users, request types, forms, workflows, roles |

## 8. Assumptions

- One shared business calendar (Monday–Friday, 08:00–17:00, Asia/Jakarta). No public-holiday calendar.
- Approval is by role plus scope: department, global or one named user.
- A request belongs to exactly one department, taken from the requester's profile at creation time.
- Business hours only count for SLA and cycle-time metrics; wall-clock timestamps are stored for the audit trail.

## 9. Success criteria

The project is successful when a request can be raised, routed, decided, reported and audited entirely inside the application, with every figure traceable to a database record and every authorisation enforced server-side.
