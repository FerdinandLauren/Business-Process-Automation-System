<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id',
        'workflow_stage_id',
        'assigned_approver_id',
        'status',
        'started_at',
        'due_at',
        'reminder_sent_at',
        'breached_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'breached_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', TaskStatus::Pending->value);
    }

    public function scopeDueSoon($query, int $hours)
    {
        return $query->pending()
            ->whereNotNull('due_at')
            ->whereNull('reminder_sent_at')
            ->where('due_at', '>', now())
            ->where('due_at', '<=', now()->addHours($hours));
    }

    public function scopeBreached($query)
    {
        return $query->pending()
            ->whereNotNull('due_at')
            ->whereNull('breached_at')
            ->where('due_at', '<', now());
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'workflow_stage_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_approver_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class)->orderBy('created_at');
    }

    public function isPending(): bool
    {
        return $this->status === TaskStatus::Pending;
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function hoursRemaining(): ?float
    {
        if ($this->due_at === null) {
            return null;
        }

        return round(now()->diffInMinutes($this->due_at, false) / 60, 1);
    }
}
