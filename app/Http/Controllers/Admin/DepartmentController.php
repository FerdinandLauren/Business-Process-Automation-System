<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('admin.departments.index', [
            'departments' => Department::query()
                ->withCount(['users', 'requests'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $department = Department::create($data);

        $audit->log('department.created', $department, ['code' => $department->code]);

        return back()->with('status', "{$department->name} was added to the organisation.");
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.form', [
            'department' => $department->loadCount(['users', 'requests']),
        ]);
    }

    public function update(Request $request, Department $department, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request, $department);
        $before = $department->only(['name', 'code', 'description']);
        $department->update($data);

        $audit->log('department.updated', $department, [
            'before' => $before,
            'after' => $department->only(['name', 'code', 'description']),
        ]);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', "{$department->name} was updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Department $department = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department?->getKey())],
            'code' => ['required', 'string', 'max:12', 'regex:/^[A-Z0-9-]+$/', Rule::unique('departments', 'code')->ignore($department?->getKey())],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'code.regex' => 'Use uppercase letters, numbers and hyphens only.',
        ]);
    }
}
