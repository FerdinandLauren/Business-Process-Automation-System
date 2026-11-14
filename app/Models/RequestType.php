<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }

    public function workflowTemplates(): HasMany
    {
        return $this->hasMany(WorkflowTemplate::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function activeFormTemplate(): ?FormTemplate
    {
        return $this->formTemplates()
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    public function activeWorkflowTemplate(): ?WorkflowTemplate
    {
        return $this->workflowTemplates()
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }
}
