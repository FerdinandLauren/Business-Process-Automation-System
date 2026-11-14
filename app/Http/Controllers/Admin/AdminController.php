<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\RequestType;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'totalUsers' => User::query()->count(),
            'departments' => Department::query()->count(),
            'requestTypes' => RequestType::query()->where('is_active', true)->count(),
            'roles' => Role::query()->count(),
            'auditEntries' => AuditLog::query()->count(),
        ]);
    }
}
