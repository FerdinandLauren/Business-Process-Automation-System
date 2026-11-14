<?php

namespace App\Models;

use App\Enums\ApproverScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_template_id',
        'stage_order',
        'stage_name',
        'approver_role_id',
        'approver_scope',
        'specific_user_id',
        'sla_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'approver_scope' => ApproverScope::class,
            'is_active' => 'boolean',
            'sla_hours' => 'integer',
            'stage_order' => 'integer',
        ];
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function specificUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specific_user_id');
    }

    public function approverScopeLabel(): string
    {
        $role = $this->approverRole?->label ?? 'Approver';

        return match ($this->approver_scope) {
            \App\Enums\ApproverScope::RequesterDepartment => $role.' · requester department',
            \App\Enums\ApproverScope::SpecificUser => $this->specificUser?->name ?? 'Specific user',
            default => $role,
        };
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ApprovalTask::class);
    }
}
