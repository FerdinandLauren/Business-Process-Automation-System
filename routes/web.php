<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RequestController;
use App\Livewire\AccountSettings;
use App\Livewire\ApprovalDetail;
use App\Livewire\ApprovalInbox;
use App\Livewire\NotificationCenter;
use App\Livewire\RequestForm;
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

    Route::get('/settings', AccountSettings::class)->name('settings.edit');
});
