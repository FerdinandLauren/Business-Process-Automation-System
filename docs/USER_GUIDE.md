# User Guide — Arunika Flow

Sign in at `/login`. Every demo account uses the password `password`. Your navigation rail only shows the sections your role can reach.

## 1. Raising a request (everyone with `request.create`)

1. Open **Request center**. Only request types that have both an active form and an active workflow are listed.
2. Choose a type, for example *Reimbursement*.
3. Fill the request details (title, priority, description) and the type-specific fields.
4. Attach any required documents. The file field tells you the allowed types and the maximum size.
5. Choose one of:
   - **Save draft** — stores the request without starting the workflow. Incomplete forms are allowed here.
   - **Submit for approval** — validates the form, checks required attachments and starts the workflow.

If submission fails, the request is kept as a draft and the fields that need attention are listed both in the alert at the top and next to each field.

### Following progress
**My requests** lists everything you raised. Open one to see the submitted data, the decision history, the stage timeline, who is holding it now and its deadline. While a request is open you can cancel it. If an approver asked for a revision, the request shows **Revision required** and you can edit and resubmit it; the workflow resumes at the same stage.

## 2. Deciding a request (approvers)

1. Open **Approval inbox**. It lists the tasks assigned to you, newest deadline first, with a badge for overdue items.
2. Open a task to see the full request, the form data, attachments, the decision history and the SLA deadline.
3. Choose **Approve**, **Request revision** or **Reject** and confirm.
   - Approval moves the request to the next stage, or completes it if this was the last stage.
   - Request revision returns it to the requester with your note.
   - Rejection closes the request permanently and requires a reason.

You can only decide tasks assigned to you, and only while they are pending. Every decision is timestamped, attributed to you and written to the audit trail.

## 3. Notifications

**Notifications** collects in-app messages: task assignments, stage advances, completions, rejections, revision requests and SLA reminders or breaches. The unread count appears in the navigation rail. Opening the centre marks items as read.

## 4. Reporting (holders of `report.view`)

**Reports** offers five reports sharing one filter bar:

| Report | Use it to answer |
| --- | --- |
| Request summary | How many requests were submitted, and how many were approved or are still open? |
| Approval summary | How many decisions reached an approver, and how quickly were they made? |
| SLA performance | Which stages meet their deadlines, and what is the compliance rate? |
| Department activity | Which departments raise the most requests, and how long do they take? |
| Status distribution | Where do the filtered requests currently sit? |

Filters: period (from/to), department, request type and status. Status options switch between request statuses and task statuses depending on the report. **Export CSV** downloads exactly the view you are looking at, with a UTF-8 BOM so Excel opens it correctly.

## 5. Administration

The administration hub is only visible to roles with administrative permissions, and each section has its own gate.

- **Users & access** — create and edit accounts, assign roles and departments, activate or deactivate users. The last active system administrator cannot be demoted or deactivated, and you cannot deactivate your own account.
- **Departments** — maintain the organisational structure.
- **Request types & forms** — create a request type, then build its form. A published form version is locked; changes create a new version cloned from it. Form fields are data: text, long text, number, date, dropdown, single choice, multiple choice and file upload, each with required and help text.
- **Workflows** — design an approval route as an ordered list of stages, each with a role, a scope (requester's department, any holder of the role, or one named person) and an SLA in business hours. Publishing supersedes the previous version. A published version, or one already used by a request, is locked.
- **Roles & permissions** — a matrix of seven roles against eight permissions. The system administrator column is locked so the system cannot be left unadministrable. Every change is audited.
- **Audit trail** — read-only, filterable by free text, action, actor and date range.

## 6. Your account

**Settings** lets you update your name, job title, email and password. Changing your password requires your current password. Deactivated accounts are signed out automatically on their next request.

## 7. Keyboard and accessibility notes

- Forms use labels, `aria-invalid` on failed fields and a summary alert at the top of the page.
- Status is conveyed by text as well as colour.
- The interface is keyboard navigable and responsive from narrow mobile widths upward.
