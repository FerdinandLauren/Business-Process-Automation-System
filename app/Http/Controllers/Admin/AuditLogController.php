<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:80'],
            'actor' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AuditLog::query()
            ->with('actor')
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $query->where(fn ($inner) => $inner
                    ->where('action', 'like', $like)
                    ->orWhere('entity_type', 'like', $like)
                    ->orWhereHas('actor', fn ($actor) => $actor
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)));
            })
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['actor'] ?? null, fn ($query, int $actor) => $query->where('actor_id', $actor))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'actors' => User::query()
                ->whereIn('id', AuditLog::query()->whereNotNull('actor_id')->distinct()->pluck('actor_id'))
                ->orderBy('name')
                ->get(),
        ]);
    }
}
