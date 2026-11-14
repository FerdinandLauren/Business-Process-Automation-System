<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'request_number',
        'request_type_id',
        'form_template_id',
        'requester_id',
        'department_id',
        'title',
        'description',
        'form_data',
        'priority',
        'status',
        'current_stage_order',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
            'priority' => Priority::class,
            'status' => RequestStatus::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class, 'request_id');
    }

    public function workflowInstance(): HasOne
    {
        return $this->hasOne(WorkflowInstance::class, 'request_id');
    }

    public function routeUrl(): string
    {
        return route('requests.show', $this);
    }

    public function isDraft(): bool
    {
        return $this->status === RequestStatus::Draft;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [RequestStatus::Draft, RequestStatus::RevisionRequired], true);
    }

    public function isCancellable(): bool
    {
        return $this->status->isOpen();
    }

    public function activeTask(): ?ApprovalTask
    {
        return $this->workflowInstance?->tasks()
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();
    }
}
