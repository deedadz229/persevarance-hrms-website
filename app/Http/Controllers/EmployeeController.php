<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::query()->orderBy('name')->get();

        $query = Employee::query()
            ->with(['department', 'position', 'user']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->query('department_id')) {
            $query->where('department_id', $departmentId);
        }

        $status = $request->query('status');
        if ($status && in_array($status, ['active', 'inactive', 'resigned'], true)) {
            $query->where('status', $status);
        } elseif ($status === 'all') {
            // Include all statuses
        } else {
            // Default: do not show resigned employees in active directory
            $query->where('status', '!=', 'resigned');
        }

        $employees = $query->latest('id')->paginate(15)->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => $departments,
            'currentSearch' => $search,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
        ]);
    }

    public function resigned(Request $request): View
    {
        $departments = Department::query()->orderBy('name')->get();

        $query = Employee::query()
            ->with(['department', 'position'])
            ->where('status', 'resigned');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->query('department_id')) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->latest('resigned_at')->paginate(15)->withQueryString();

        return view('employees.resigned', [
            'employees' => $employees,
            'departments' => $departments,
            'currentSearch' => $search,
            'currentDepartment' => $departmentId,
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureCanManageEmployees($request);

        $nextEmployeeCode = Employee::generateNextEmployeeCode();
        $departments = Department::query()->where('status', 'active')->orderBy('name')->get();
        $positions = Position::query()->where('status', 'active')->orderBy('name')->get();

        return view('employees.create', [
            'nextEmployeeCode' => $nextEmployeeCode,
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageEmployees($request);

        $rules = [
            'employee_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => [
                'required',
                'integer',
                Rule::exists('positions', 'id')->where(function ($q) use ($request) {
                    $departmentId = $request->input('department_id');
                    if ($departmentId) {
                        $q->where('department_id', $departmentId);
                    }
                }),
            ],
            'join_date' => ['required', 'date'],
            'employment_type' => ['required', 'in:internship,contract,permanent'],
            'status' => ['required', 'in:active,inactive,resigned'],
            'special_status' => ['nullable', 'string', 'max:100'],
            'resigned_at' => ['nullable', 'date', 'required_if:status,resigned'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'create_account' => ['nullable'],
            'account_password' => ['nullable', 'required_if:create_account,1', 'string', 'min:8', 'confirmed'],
        ];

        $validator = Validator::make($request->all(), $rules, [
            'photo.image' => 'File foto profil harus berupa gambar.',
            'photo.mimes' => 'Format foto profil harus JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran file foto profil maksimal 2 MB.',
            'email.unique' => 'Email ini sudah terdaftar pada data karyawan.',
            'account_password.required_if' => 'Password akun pengguna wajib diisi jika membuat akun.',
            'account_password.min' => 'Password akun minimal terdiri dari 8 karakter.',
            'account_password.confirmed' => 'Konfirmasi password akun tidak cocok.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->boolean('create_account') && $request->filled('email')) {
                if (User::where('email', $request->input('email'))->exists()) {
                    $validator->errors()->add('email', 'Email karyawan ini sudah digunakan oleh akun pengguna lain.');
                }
            }
        });

        $validated = $validator->validate();

        if ($validated['status'] !== 'resigned') {
            $validated['resigned_at'] = null;
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'emp_'.Str::random(24).'.'.$file->getClientOriginalExtension();
            $photoPath = $file->storeAs('employees', $filename, 'public');
        }

        try {
            $employee = DB::transaction(function () use ($validated, $photoPath, $request) {
                $validated['employee_code'] = Employee::generateNextEmployeeCode();
                if ($photoPath) {
                    $validated['photo_path'] = $photoPath;
                    $validated['photo_version'] = 1;
                }

                $employeeData = collect($validated)->except([
                    'photo',
                    'create_account',
                    'account_password',
                    'account_password_confirmation',
                ])->toArray();

                $employee = Employee::create($employeeData);

                if ($request->boolean('create_account')) {
                    $employeeRole = Role::firstOrCreate(
                        ['name' => 'employee'],
                        ['description' => 'Akses pengguna karyawan mobile/portal.']
                    );

                    $user = User::create([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'role_id' => $employeeRole->id,
                        'employee_id' => $employee->id,
                        'password' => Hash::make($request->input('account_password')),
                        'status' => 'active',
                    ]);

                    AuditLog::create([
                        'user_id' => $request->user()?->id,
                        'action' => 'employee_account_created',
                        'entity_type' => User::class,
                        'entity_id' => $user->id,
                        'old_values' => null,
                        'new_values' => [
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => 'employee',
                            'employee_id' => $employee->id,
                        ],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'created_at' => now(),
                    ]);
                }

                return $employee;
            });
        } catch (\Throwable $e) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $e;
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'employee_created',
            'entity_type' => Employee::class,
            'entity_id' => $employee->id,
            'old_values' => null,
            'new_values' => $employee->only([
                'employee_code',
                'name',
                'email',
                'phone',
                'department_id',
                'position_id',
                'join_date',
                'employment_type',
                'status',
                'special_status',
                'resigned_at',
                'photo_path',
                'photo_version',
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function show(Employee $employee): View
    {
        $employee->loadMissing(['department', 'position', 'user']);

        return view('employees.show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Request $request, Employee $employee): View
    {
        $this->ensureCanManageEmployees($request);

        $employee->loadMissing(['user', 'department', 'position']);
        $departments = Department::query()->orderBy('name')->get();
        $positions = Position::query()->orderBy('name')->get();

        return view('employees.edit', [
            'employee' => $employee,
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensureCanManageEmployees($request);

        $hasExistingUser = $employee->user()->exists();
        $existingUserId = $employee->user?->id;

        $rules = [
            'employee_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_code')->ignore($employee->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => [
                'required',
                'integer',
                Rule::exists('positions', 'id')->where(function ($q) use ($request) {
                    $departmentId = $request->input('department_id');
                    if ($departmentId) {
                        $q->where('department_id', $departmentId);
                    }
                }),
            ],
            'join_date' => ['required', 'date'],
            'employment_type' => ['required', 'in:internship,contract,permanent'],
            'status' => ['required', 'in:active,inactive,resigned'],
            'special_status' => ['nullable', 'string', 'max:100'],
            'resigned_at' => ['nullable', 'date', 'required_if:status,resigned'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'delete_photo' => ['nullable'],
        ];

        if ($hasExistingUser) {
            $rules['account_password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['create_account'] = ['nullable'];
            $rules['account_password'] = [
                'nullable',
                'required_if:create_account,1',
                'string',
                'min:8',
                'confirmed',
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'photo.image' => 'File foto profil harus berupa gambar.',
            'photo.mimes' => 'Format foto profil harus JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran file foto profil maksimal 2 MB.',
            'email.unique' => 'Email ini sudah terdaftar pada data karyawan lain.',
            'account_password.required_if' => 'Password akun pengguna wajib diisi jika membuat akun.',
            'account_password.min' => 'Password akun baru minimal terdiri dari 8 karakter.',
            'account_password.confirmed' => 'Konfirmasi password akun tidak cocok.',
        ]);

        $validator->after(function ($validator) use ($request, $hasExistingUser, $existingUserId) {
            if ($request->filled('email')) {
                $email = $request->input('email');
                if ($hasExistingUser) {
                    $collision = User::where('email', $email)->where('id', '!=', $existingUserId)->exists();
                    if ($collision) {
                        $validator->errors()->add('email', 'Email karyawan ini sudah digunakan oleh akun pengguna lain.');
                    }
                } elseif ($request->boolean('create_account')) {
                    if (User::where('email', $email)->exists()) {
                        $validator->errors()->add('email', 'Email karyawan ini sudah digunakan oleh akun pengguna lain.');
                    }
                }
            }
        });

        $validated = $validator->validate();

        if ($validated['status'] !== 'resigned') {
            $validated['resigned_at'] = null;
        }

        // Retain original employee_code (never change on edit)
        $validated['employee_code'] = $employee->employee_code;

        $oldPhotoPath = $employee->photo_path;
        $newPhotoPath = $oldPhotoPath;
        $photoVersion = $employee->photo_version ?: 1;
        $photoChanged = false;
        $tempUploadedPath = null;

        if ($request->boolean('delete_photo')) {
            $newPhotoPath = null;
            $photoVersion++;
            $photoChanged = true;
        } elseif ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'emp_'.Str::random(24).'.'.$file->getClientOriginalExtension();
            $newPhotoPath = $file->storeAs('employees', $filename, 'public');
            $tempUploadedPath = $newPhotoPath;
            $photoVersion++;
            $photoChanged = true;
        }

        $validated['photo_path'] = $newPhotoPath;
        $validated['photo_version'] = $photoVersion;

        $fields = [
            'employee_code',
            'name',
            'email',
            'phone',
            'department_id',
            'position_id',
            'join_date',
            'employment_type',
            'status',
            'special_status',
            'resigned_at',
            'photo_path',
            'photo_version',
        ];

        $oldValues = $employee->only($fields);

        try {
            DB::transaction(function () use ($request, $employee, $validated, $fields, $hasExistingUser) {
                $employeeData = collect($validated)->only($fields)->toArray();
                $employee->update($employeeData);

                if ($hasExistingUser) {
                    $user = $employee->user;
                    $userUpdate = [];
                    if ($validated['name'] !== $user->name) {
                        $userUpdate['name'] = $validated['name'];
                    }
                    if ($validated['email'] !== $user->email) {
                        $userUpdate['email'] = $validated['email'];
                    }
                    if ($request->filled('account_password')) {
                        $userUpdate['password'] = Hash::make($request->input('account_password'));
                    }
                    if (! empty($userUpdate)) {
                        $oldUserValues = $user->only(['name', 'email', 'status']);
                        $user->update($userUpdate);

                        AuditLog::create([
                            'user_id' => $request->user()?->id,
                            'action' => 'employee_account_updated',
                            'entity_type' => User::class,
                            'entity_id' => $user->id,
                            'old_values' => $oldUserValues,
                            'new_values' => $user->only(['name', 'email', 'status']),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                            'created_at' => now(),
                        ]);
                    }
                } elseif ($request->boolean('create_account')) {
                    $employeeRole = Role::firstOrCreate(
                        ['name' => 'employee'],
                        ['description' => 'Akses pengguna karyawan mobile/portal.']
                    );

                    $user = User::create([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'role_id' => $employeeRole->id,
                        'employee_id' => $employee->id,
                        'password' => Hash::make($request->input('account_password')),
                        'status' => 'active',
                    ]);

                    AuditLog::create([
                        'user_id' => $request->user()?->id,
                        'action' => 'employee_account_created',
                        'entity_type' => User::class,
                        'entity_id' => $user->id,
                        'old_values' => null,
                        'new_values' => [
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => 'employee',
                            'employee_id' => $employee->id,
                        ],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'created_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            if ($tempUploadedPath && Storage::disk('public')->exists($tempUploadedPath)) {
                Storage::disk('public')->delete($tempUploadedPath);
            }

            throw $e;
        }

        // Clean up old photo if photo was successfully changed and old file existed
        if ($photoChanged && $oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        $newValues = $employee->fresh()->only($fields);

        $action = ($oldValues['status'] !== 'resigned' && $newValues['status'] === 'resigned')
            ? 'employee_resigned'
            : 'employee_updated';

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => Employee::class,
            'entity_id' => $employee->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if ($photoChanged) {
            AuditLog::create([
                'user_id' => $request->user()?->id,
                'action' => 'employee_photo_updated',
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'old_values' => ['photo_path' => $oldPhotoPath],
                'new_values' => ['photo_path' => $newPhotoPath, 'photo_version' => $photoVersion],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensureCanManageEmployees($request);

        // Historical integrity protection: do not delete employees with linked data or resigned employees
        if ($employee->attendances()->exists() || $employee->payrolls()->exists() || $employee->leaveRequests()->exists() || $employee->overtimes()->exists()) {
            return redirect()
                ->route('employees.index')
                ->with('error', 'Karyawan memiliki riwayat aktivitas (absensi/cuti/lembur/payroll) dan tidak boleh dihapus. Ubah status menjadi resigned.');
        }

        if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        $employee->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'employee_deleted',
            'entity_type' => Employee::class,
            'entity_id' => $employee->id,
            'old_values' => $employee->toArray(),
            'new_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Karyawan berhasil dihapus.');
    }

    private function ensureCanManageEmployees(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role?->name === 'taiwan_account') {
            abort(403, 'Taiwan Account bersifat read-only dan tidak diizinkan mengubah data.');
        }

        $hasPermission = $user->role?->permissions->contains('name', 'manage_employees');
        if (! $hasPermission) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola data karyawan.');
        }
    }
}
