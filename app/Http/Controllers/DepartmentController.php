<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    private function ensureCanManageDepartments(Request $request): void
    {
        if ($request->user()?->role?->name !== 'super_admin') {
            abort(403, 'Hanya Super Admin yang memiliki hak akses untuk mengelola data department.');
        }
    }

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Department::query()
            ->withCount('employees');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $departments = $query->latest('id')->paginate(15)->withQueryString();

        return view('departments.index', compact('departments', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        $this->ensureCanManageDepartments($request);

        return view('departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageDepartments($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'name.required' => 'Nama department wajib diisi.',
            'name.unique' => 'Nama department sudah digunakan.',
            'status.required' => 'Status department wajib dipilih.',
            'status.in' => 'Status department harus active atau inactive.',
        ]);

        $department = Department::create($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'department_created',
            'entity_type' => Department::class,
            'entity_id' => $department->id,
            'old_values' => null,
            'new_values' => $department->only(['name', 'description', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department berhasil ditambahkan.');
    }

    public function show(Department $department): View
    {
        $department->loadCount('employees');
        $employees = $department->employees()
            ->with('position')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('departments.show', compact('department', 'employees'));
    }

    public function edit(Request $request, Department $department): View
    {
        $this->ensureCanManageDepartments($request);

        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->ensureCanManageDepartments($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'name.required' => 'Nama department wajib diisi.',
            'name.unique' => 'Nama department sudah digunakan.',
            'status.required' => 'Status department wajib dipilih.',
            'status.in' => 'Status department harus active atau inactive.',
        ]);

        $oldValues = $department->only(['name', 'description', 'status']);
        $department->update($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'department_updated',
            'entity_type' => Department::class,
            'entity_id' => $department->id,
            'old_values' => $oldValues,
            'new_values' => $department->only(['name', 'description', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department berhasil diperbarui.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $this->ensureCanManageDepartments($request);

        if ($department->employees()->exists()) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Department tidak dapat dihapus karena masih digunakan oleh karyawan.');
        }

        if ($department->positions()->exists()) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Department tidak dapat dihapus karena masih memiliki jabatan.');
        }

        $oldValues = $department->only(['name', 'description', 'status']);
        $department->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'department_deleted',
            'entity_type' => Department::class,
            'entity_id' => $department->id,
            'old_values' => $oldValues,
            'new_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department berhasil dihapus.');
    }
}
