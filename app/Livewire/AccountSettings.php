<?php

namespace App\Livewire;

use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Account settings')]
class AccountSettings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $job_title = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->job_title = (string) $user->job_title;
    }

    public function updateProfile(AuditLogger $audit): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->getKey())],
            'job_title' => ['nullable', 'string', 'max:120'],
        ]);

        $user->forceFill($validated)->save();

        $audit->log('user.profile_updated', $user, ['email' => $user->email], $user);

        session()->flash('status', 'Profile updated.');
    }

    public function updatePassword(AuditLogger $audit): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
        ]);

        auth()->user()->forceFill(['password' => $this->password])->save();

        $audit->log('user.password_changed', auth()->user(), [], auth()->user());

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('status', 'Password updated. Other sessions remain signed in.');
    }

    public function render()
    {
        return view('livewire.account-settings', [
            'user' => auth()->user()->load('department', 'roles'),
            'roles' => auth()->user()->roles,
            'permissions' => auth()->user()->roles->flatMap->permissions->pluck('label')->unique()->sort()->values(),
        ]);
    }
}
