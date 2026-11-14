# Database Design — Arunika Flow

Nine domain migrations plus Laravel's defaults. Written for SQLite locally and PostgreSQL 16 in production; no SQLite-only column types or functions are used in business queries.

## 1. Entity overview

```
departments ──┬── users ──┬── role_user ── roles ── permission_role ── permissions
              │           │
              │           ├── requests ──┬── request_attachments
              │           │              └── workflow_instances ──┬── approval_tasks ── approval_histories
              │           │                                      └── workflow_events
              │           └── notifications
              │
request_types ──┬── form_templates
                └── workflow_templates ── workflow_stages
audit_logs (polymorphic by entity_type + entity_id)
request_number_sequences (monthly counter)
```

## 2. Organisation and access

### `departments`
`id`, `name` (unique), `code` (unique, 12), `description`, timestamps.

### `users` (extended)
Laravel's table plus `department_id` (nullable, `nullOnDelete`), `employee_code` (unique, 24), `job_title`, `is_active` (default true), `last_login_at`.

### `roles`
`id`, `name` (unique), `label`, `description`, `is_system`, timestamps. Seeded: `employee`, `department_manager`, `finance_manager`, `finance_admin`, `process_admin`, `system_admin`, `management`.

### `permissions`
`id`, `name` (unique), `label`, `description`, timestamps. Eight permissions: `request.create`, `request.view.any`, `approval.process`, `workflow.manage`, `user.manage`, `organisation.manage`, `report.view`, `audit.view`.

### `role_user`, `permission_role`
Composite-primary-key pivot tables with cascading deletes.

### `audit_logs`
`id`, `actor_id` (nullable, `nullOnDelete`), `action` (96), `entity_type` (96, class basename), `entity_id`, `metadata` (JSON), `ip_address`, `user_agent`, `created_at`. Indexed on `(entity_type, entity_id)`, `action` and `created_at`.

The application never updates or deletes rows here; the UI exposes read-only filtering.

## 3. Request domain

### `request_types`
`id`, `name` (unique), `code` (unique, 32), `description`, `is_active`, timestamps. Seeded: `REIMB`, `LEAVE`, `ITACC`, `PURCH`.

### `form_templates`
`id`, `request_type_id`, `name`, `version` (default 1), `schema` (JSON), `is_active`, `published_at`, `created_by`, timestamps. Unique on `(request_type_id, version)`.

`schema.fields[]` is a list of field definitions: `key`, `label`, `type` (`text`, `textarea`, `number`, `date`, `dropdown`, `radio`, `checkbox`, `file`), `required`, `options`, `help`, `placeholder`. `FormDataValidator` compiles this into Laravel validation rules, so a new field needs no code change.

### `request_number_sequences`
`month_key` (primary, `YYYYMM`), `last_number`. Allocates `REQ-YYYYMM-NNNN` inside the submission transaction.

### `requests`
`id`, `request_number` (unique, 32), `request_type_id` (`restrictOnDelete`), `form_template_id` (nullable), `requester_id` (`restrictOnDelete`), `department_id` (nullable), `title`, `description`, `form_data` (JSON), `priority` (16), `status` (32), `current_stage_order`, `submitted_at`, `completed_at`, timestamps. Indexed on `status`, `department_id`, `requester_id`, `submitted_at`.

`status` is the `RequestStatus` enum: `draft`, `submitted`, `under_review`, `revision_required`, `approved`, `rejected`, `cancelled`.

### `request_attachments`
`id`, `request_id` (cascade), `uploaded_by` (`restrictOnDelete`), `field_key` (nullable, links a file to a form field), `original_filename`, `stored_filename` (UUID + extension), `mime_type`, `file_size`, `storage_path`, timestamps.

Files are stored under `storage/app/private/attachments/YYYY/MM` by default; the database stores only metadata and a relative path. Downloads are authorised through `ServiceRequestPolicy::downloadAttachment` and sent with `X-Content-Type-Options: nosniff`.

## 4. Workflow domain

### `workflow_templates`
`id`, `request_type_id` (cascade), `name`, `description`, `version`, `is_active`, `published_at`, `superseded_at`, `created_by`, timestamps. Unique on `(request_type_id, version)`; indexed on `(request_type_id, is_active)`.

### `workflow_stages`
`id`, `workflow_template_id` (cascade), `stage_order`, `stage_name`, `approver_role_id` (`restrictOnDelete`), `approver_scope` (32), `specific_user_id` (nullable), `sla_hours` (default 24), `is_active`, timestamps. Unique on `(workflow_template_id, stage_order)`.

`approver_scope` is one of `requester_department`, `global`, `specific_user`.

### `workflow_instances`
`id`, `request_id` (unique, cascade), `workflow_template_id` (`restrictOnDelete`), `workflow_version`, `current_stage_order`, `status` (24), `started_at`, `completed_at`, timestamps. Indexed on `status`.

`status` is the `InstanceStatus` enum: `running`, `completed`, `rejected`, `cancelled`.

### `approval_tasks`
`id`, `workflow_instance_id` (cascade), `workflow_stage_id` (`restrictOnDelete`), `assigned_approver_id` (nullable), `status` (24), `started_at`, `due_at`, `reminder_sent_at`, `breached_at`, `completed_at`, timestamps. Indexed on `(assigned_approver_id, status)`, `status` and `due_at`.

`status` is the `TaskStatus` enum: `pending`, `approved`, `rejected`, `revision_requested`, `cancelled`. `reminder_sent_at` and `breached_at` are claim columns that make the scheduled monitor idempotent.

### `approval_histories`
`id`, `approval_task_id` (cascade), `actor_id` (nullable), `action` (32), `comment`, `created_at`. Indexed on `(approval_task_id, created_at)`.

### `workflow_events`
`id`, `workflow_instance_id` (cascade), `event_type` (48), `payload` (JSON), `occurred_at`. Indexed on `(workflow_instance_id, occurred_at)`.

Recorded events: `workflow.started`, `task.created`, `workflow.stage_advanced`, `workflow.completed`, `workflow.rejected`/`terminated`, `workflow.revision_requested`, `workflow.resumed`. These give each request a readable timeline independent of the audit log.

## 5. Notifications

### `notifications`
`id`, `user_id`, `request_id` (nullable), `type`, `title`, `message`, `url`, `read_at`, timestamps.

Types in use: `task.assigned`, `request.submitted`, `request.resubmitted`, `request.approved_stage`, `request.completed`, `request.rejected`, `request.revision_required`, `sla.reminder`, `sla.breached`.

## 6. Integrity rules

- Deleting a request cascades to attachments, the workflow instance, its tasks, histories and events.
- Deleting a user who raised a request is restricted; deactivating is the supported path.
- Deleting a role that a stage depends on is restricted, so routing cannot silently break.
- Composite unique keys prevent duplicate versions and duplicate stage orders.
- `workflow_instances.request_id` is unique: one request can never have two workflows.

## 7. Portability notes

- JSON columns use Laravel's `json` type, which maps to `text` on SQLite and `jsonb` on PostgreSQL.
- Timestamps are stored in the application timezone (`Asia/Jakarta`) as `Y-m-d H:i:s` strings by Eloquent.
- `lockForUpdate()` is a no-op on SQLite; the conditional-update guard carries the correctness requirement on both engines.
