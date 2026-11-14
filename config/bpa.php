<?php

return [
    'company' => [
        'name' => 'PT Arunika Industrial Solutions',
        'short_name' => 'Arunika',
        'product' => 'Arunika Flow',
        'tagline' => 'Process Automation Workspace',
        'email' => 'operations@arunika.example',
    ],

    'timezone' => env('BPA_TIMEZONE', 'Asia/Jakarta'),

    'attachments' => [
        'disk' => env('BPA_ATTACHMENT_DISK', 'local'),
        'directory' => 'attachments',
        'max_kb' => (int) env('BPA_ATTACHMENT_MAX_KB', 5120),
        'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'xlsx', 'csv'],
        'mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
        ],
    ],

    'sla' => [
        // Business calendar used by the SLA calculator (MVP: one shared calendar).
        'workday_start' => '08:00',
        'workday_end' => '17:00',
        'workdays' => [1, 2, 3, 4, 5],
        'reminder_hours_before' => (int) env('BPA_SLA_REMINDER_HOURS', 4),
    ],
];
