<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['role', 'employee'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->query('role_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('users.create', compact('roles', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'employee_id' => ['nullable', 'exists:employees,id', 'unique:users,employee_id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
        ];

        $messages = [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan oleh akun lain.',
            'role_id.required' => 'Pilihan role wajib ditentukan.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            'employee_id.exists' => 'Data karyawan tidak valid.',
            'employee_id.unique' => 'Karyawan yang dipilih sudah memiliki akun pengguna.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'status.required' => 'Status akun wajib dipilih.',
            'status.in' => 'Status akun tidak valid.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('employee_id')) {
                $linkedEmployee = Employee::find($request->input('employee_id'));
                if ($linkedEmployee && $request->input('email') !== $linkedEmployee->email) {
                    $validator->errors()->add('email', "Email pengguna untuk akun karyawan harus sama persis dengan email karyawan ({$linkedEmployee->email}).");
                }
            }
        });

        $validated = $validator->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'employee_id' => $validated['employee_id'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'user_created',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'old_values' => null,
            'new_values' => $user->only(['name', 'email', 'role_id', 'employee_id', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', "Akun pengguna {$user->name} berhasil dibuat.");
    }

    public function show(User $user): View
    {
        $user->load(['role.permissions', 'employee']);

        $recentLogs = AuditLog::where('user_id', $user->id)
            ->orWhere(function ($q) use ($user) {
                $q->where('entity_type', User::class)
                    ->where('entity_id', $user->id);
            })
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('users.show', compact('user', 'recentLogs'));
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles', 'employees'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'employee_id' => ['nullable', 'exists:employees,id', Rule::unique('users', 'employee_id')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
        ];

        $messages = [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan oleh akun lain.',
            'role_id.required' => 'Pilihan role wajib ditentukan.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            'employee_id.exists' => 'Data karyawan tidak valid.',
            'employee_id.unique' => 'Karyawan yang dipilih sudah terhubung dengan akun pengguna lain.',
            'password.min' => 'Password baru minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'status.required' => 'Status akun wajib dipilih.',
            'status.in' => 'Status akun tidak valid.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('employee_id')) {
                $linkedEmployee = Employee::find($request->input('employee_id'));
                if ($linkedEmployee && $request->input('email') !== $linkedEmployee->email) {
                    $validator->errors()->add('email', "Email pengguna untuk akun karyawan harus sama persis dengan email karyawan ({$linkedEmployee->email}).");
                }
            }
        });

        $validated = $validator->validate();

        // Proteksi 1: Super Admin tidak boleh menonaktifkan dirinya sendiri
        if ($user->id === $request->user()?->id && $validated['status'] === 'inactive') {
            return back()->withInput()->with('error', 'Anda tidak dapat menonaktifkan akun Super Admin Anda sendiri.');
        }

        $targetRole = Role::find($validated['role_id']);

        // Proteksi 2: Super Admin tidak boleh mengubah role dirinya sendiri menjadi role lain
        if ($user->id === $request->user()?->id && $user->role?->name === 'super_admin' && $targetRole->name !== 'super_admin') {
            return back()->withInput()->with('error', 'Anda tidak dapat mencabut role Super Admin dari akun Anda sendiri.');
        }

        // Proteksi 3: Pastikan selalu ada minimal 1 Super Admin aktif
        if ($user->role?->name === 'super_admin' && ($validated['status'] === 'inactive' || $targetRole->name !== 'super_admin')) {
            $activeSuperAdmins = User::whereHas('role', function ($q) {
                $q->where('name', 'super_admin');
            })->where('status', 'active')->where('id', '!=', $user->id)->count();

            if ($activeSuperAdmins < 1) {
                return back()->withInput()->with('error', 'Sistem harus memiliki minimal satu akun Super Admin yang aktif.');
            }
        }

        $oldValues = $user->only(['name', 'email', 'role_id', 'employee_id', 'status']);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'employee_id' => $validated['employee_id'] ?? null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'user_updated',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $user->only(['name', 'email', 'role_id', 'employee_id', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        if ($newStatus === 'inactive') {
            // Proteksi 1: Super Admin tidak boleh menonaktifkan dirinya sendiri
            if ($user->id === $request->user()?->id) {
                return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            }

            // Proteksi 2: Pastikan selalu ada minimal 1 Super Admin aktif
            if ($user->role?->name === 'super_admin') {
                $activeSuperAdmins = User::whereHas('role', function ($q) {
                    $q->where('name', 'super_admin');
                })->where('status', 'active')->where('id', '!=', $user->id)->count();

                if ($activeSuperAdmins < 1) {
                    return back()->with('error', 'Sistem harus memiliki minimal satu akun Super Admin yang aktif.');
                }
            }
        }

        $oldStatus = $user->status;
        $user->update(['status' => $newStatus]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $newStatus === 'active' ? 'user_activated' : 'user_deactivated',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $statusText = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun pengguna {$user->name} berhasil {$statusText}.");
    }
}
