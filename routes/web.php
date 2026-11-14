<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\RequestTypeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\WorkflowController;
use App\Livewire\AccountSettings;
use App\Livewire\ApprovalDetail;
use App\Livewire\ApprovalInbox;
use App\Livewire\FormTemplateEditor;
use App\Livewire\NotificationCenter;
use App\Livewire\RequestForm;
use App\Livewire\WorkflowEditor;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/overview');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/overview', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/request-center', [RequestController::class, 'center'])->name('requests.center');
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create/{type}', RequestForm::class)->name('requests.create');
    Route::get('/requests/{request}/edit', RequestForm::class)->name('requests.edit');
    Route::get('/requests/{request}', [RequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{request}/submit', [RequestController::class, 'submit'])->name('requests.submit');
    Route::post('/requests/{request}/cancel', [RequestController::class, 'cancel'])->name('requests.cancel');
    Route::get('/requests/{request}/attachments/{attachment}', [RequestController::class, 'download'])->name('requests.attachments.download');

    Route::get('/approvals', ApprovalInbox::class)->name('approvals.index');
    Route::get('/approvals/{task}', ApprovalDetail::class)->name('approvals.show');

    Route::get('/notifications', NotificationCenter::class)->name('notifications.index');

    Route::middleware('can:manage-workflows')->group(function () {
        Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
        Route::get('/workflows/new/{type}', WorkflowEditor::class)->name('workflows.create');
        Route::get('/workflows/{template}', WorkflowEditor::class)->name('workflows.edit');
        Route::post('/workflows/{template}/version', [WorkflowController::class, 'version'])->name('workflows.version');
    });

    Route::middleware('can:view-reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');

        Route::middleware('can:manage-users')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');

            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::patch('/roles', [RoleController::class, 'update'])->name('roles.update');
        });

        Route::middleware('can:manage-organisation')->group(function () {
            Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
            Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
            Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        });

        Route::middleware('can:manage-workflows')->group(function () {
            Route::get('/request-types', [RequestTypeController::class, 'index'])->name('request-types.index');
            Route::get('/request-types/create', [RequestTypeController::class, 'create'])->name('request-types.create');
            Route::post('/request-types', [RequestTypeController::class, 'store'])->name('request-types.store');
            Route::get('/request-types/{requestType}/edit', [RequestTypeController::class, 'edit'])->name('request-types.edit');
            Route::put('/request-types/{requestType}', [RequestTypeController::class, 'update'])->name('request-types.update');
            Route::patch('/request-types/{requestType}/status', [RequestTypeController::class, 'toggleStatus'])->name('request-types.status');

            Route::get('/request-types/{requestType}/forms/new', FormTemplateEditor::class)->name('request-types.forms.create');
            Route::get('/request-types/{requestType}/forms/{template}', FormTemplateEditor::class)->name('request-types.forms.edit');
        });

        Route::middleware('can:view-audit-logs')->group(function () {
            Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        });
    });

    Route::get('/settings', AccountSettings::class)->name('settings.edit');
});
