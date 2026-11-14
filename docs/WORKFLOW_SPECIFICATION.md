# Workflow Specification — Arunika Flow

## 1. Stages

A workflow is an ordered list of stages belonging to one request type. Each stage defines:

| Field | Meaning |
| --- | --- |
| `stage_order` | 1-based position; unique per template |
| `stage_name` | Human label shown in the inbox, timeline and reports |
| `approver_role_id` | Role the approver must hold |
| `approver_scope` | `requester_department`, `global` or `specific_user` |
| `specific_user_id` | The named approver when scope is `specific_user` |
| `sla_hours` | Business hours allowed for a decision at this stage |
| `is_active` | Inactive stages are skipped when the engine computes the route |

## 2. Approver resolution

`WorkflowEngine::resolveApprover()` applies these rules in order:

1. **Specific user** — the named user is returned if they exist and are active; otherwise no approver is found.
2. **Role + department scope** — active users holding the stage role **in the requester's department**.
3. **Role + global scope** — active users holding the stage role anywhere.

If more than one candidate remains, the requester is removed from the list (a person cannot approve their own request). The lowest user id wins, which makes routing deterministic and therefore testable.

If no candidate exists, the transition fails loudly (`WorkflowException`) rather than silently completing or parking the request. The request then cannot be submitted until configuration is fixed — which is preferable to a request that nobody can decide.

## 3. Lifecycle

### Start
On submission: `requests.status = submitted` and `submitted_at = now()`, then `WorkflowEngine::start()` creates the instance (`running`, `current_stage_order` = first stage), sets `requests.status = under_review`, creates the first task with `due_at = addBusinessHours(now, stage.sla_hours)`, notifies the approver and records `workflow.started` + `task.created`.

### Advance
On approval of a non-final stage: a task is created for the next active stage, `current_stage_order` moves forward on both the instance and the request, the new approver is notified and `workflow.stage_advanced` is recorded.

### Complete
On approval of the final stage: the instance becomes `completed`, the request becomes `approved` with `completed_at`, the requester is notified and `request.approved` is audited.

### Reject
`terminate(instance, Rejected, comment)`: the instance becomes `rejected`, the request becomes `rejected` with `completed_at`, every other pending task is cancelled, the requester is notified with the reason and `approval.rejected` is audited. A rejected request is final.

### Request revision
`markRevisionRequired()`: the request becomes `revision_required` while the instance stays `running`. The requester is notified with the note. On resubmission `resumeAfterRevision()` creates a fresh task at the **same** stage and returns the request to `under_review`. Revision time is therefore not counted against the approver's SLA.

### Cancel
Only the requester, and only while the request is open: the instance becomes `cancelled`, the request becomes `cancelled`, pending tasks are cancelled and the action is audited.

## 4. Decisions

A decision is one of `approved`, `rejected`, `revision_requested`.

- Rejection and revision require a written reason of at least five characters; approval may carry an optional comment.
- Every decision writes an `approval_histories` row with the actor, action and comment.
- The decision is applied in a transaction with a row lock and a conditional update (`where status = 'pending'`). If the affected-row count is not exactly one, the decision is refused — this is the double-decision guard.

## 5. SLA

- Working hours are Monday–Friday, 08:00–17:00, Asia/Jakarta, configured in `config/bpa.php`.
- `SLAService::addBusinessHours()` skips weekends and non-working hours when computing a due date.
- `SLAService::businessMinutesBetween()` counts only working minutes, and is used for cycle and decision times in reports.
- A task is **due soon** when it is within `reminder_hours_before` (default 4) hours of its deadline, **overdue** when the deadline has passed.
- `bpa:sla-monitor` runs every fifteen minutes:
  - due-soon tasks are claimed via `reminder_sent_at` and the approver is reminded;
  - overdue tasks are claimed via `breached_at`, an `approval.sla_breached` audit record is written, and both the approver and the requester are notified.
- Claiming is a conditional update, so a task is reminded and breached at most once. `--dry-run` prints the pending actions without changing anything.
- **No escalation.** A breach is recorded and visible; it does not reassign the task. This is a deliberate MVP boundary.

## 6. Configuration changes

- Publishing a new workflow version deactivates and supersedes the previous active version for that type.
- Published versions, and any version with instances, are locked; changes create a new version cloned from the current one.
- In-flight instances keep their pinned `workflow_template_id` and `workflow_version`, so a configuration change never alters a running approval.
- Request types with no active workflow are hidden from the request centre, so a user can never start a request that cannot be routed.

## 7. Seeded routes

| Request type | Stages |
| --- | --- |
| Reimbursement (`REIMB`) | Department Manager (dept scope, 24h) → Finance Manager (global, 48h) → Finance Processing (global, 72h) |
| Leave (`LEAVE`) | Department Manager (dept scope, 24h) |
| IT Access (`ITACC`) | Department Manager (dept scope, 24h) → IT Manager (specific user, 16h) → IT Provisioning (specific user, 8h) |
| Purchase (`PURCH`) | Department Manager (dept scope, 24h) → Procurement Review (global, 48h) → Finance Manager (global, 48h) |

## 8. Edge cases and how they behave

| Case | Behaviour |
| --- | --- |
| Requester is the only person holding the approver role in their department | They are not excluded (exclusion only applies when several candidates exist), so the route still resolves |
| A named approver is deactivated | Resolution fails and the transition is refused; an administrator must fix the configuration |
| Two approvers decide at once | The second decision changes zero rows and is refused |
| A task is decided after the request was cancelled | Refused: the request is no longer `under_review` |
| A stage is deactivated mid-flight | It is skipped when the engine computes the next stage |
| Draft saved with missing required fields | Allowed; submission is refused until complete |
| Required attachment missing | Submission is refused with a field-level error |
