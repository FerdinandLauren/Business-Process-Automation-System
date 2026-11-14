<?php

namespace App\Models;

use App\Enums\InstanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'workflow_template_id',
        'workflow_version',
        'current_stage_order',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InstanceStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ApprovalTask::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class)->orderBy('occurred_at');
    }

    public function isRunning(): bool
    {
        return $this->status === InstanceStatus::Running;
    }
}
