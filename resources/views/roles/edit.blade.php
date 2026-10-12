@extends('layouts.app')

@section('title', __('roles.configure_role_title', ['label' => $role->label]))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .form-card { padding: 28px; max-width: 960px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 7px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13.5px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .group-box { margin-bottom: 24px; border: 1px solid var(--line); border-radius: 10px; padding: 16px 18px; background: #fff; }
    .group-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1px solid var(--line); padding-bottom: 10px; }
    .group-header h4 { margin: 0; font-size: 14px; font-weight: 700; color: var(--brand-dark); }
    .perm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
    .perm-item { display: flex; align-items: flex-start; gap: 10px; padding: 8px 10px; border-radius: 6px; transition: background .12s ease; cursor: pointer; }
    .perm-item:hover { background: #f4fbf9; }
    .perm-item input[type="checkbox"] { margin-top: 3px; cursor: pointer; accent-color: var(--brand); width: 16px; height: 16px; }
    .perm-info strong { display: block; font-size: 12.5px; font-weight: 600; color: var(--ink); font-family: ui-monospace, monospace; }
    .perm-info span { display: block; font-size: 11.5px; color: var(--muted); margin-top: 2px; }
    .form-actions { display: flex; align-items: center; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--line); }
    .alert-banner { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 12.5px; line-height: 1.5; background: #fff9db; border: 1px solid #ffe066; color: #7c5e10; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('roles.configure_role_title', ['label' => $role->label]) }}</h2>
        <p>{{ __('roles.configure_role_sub', ['label' => $role->label]) }}</p>
    </div>
    <div>
        <a href="{{ route('roles.show', $role) }}" class="btn btn-outline">&larr; {{ __('common.cancel') }}</a>
    </div>
</div>

<div class="card form-card">
    @if ($role->name === 'super_admin')
        <div class="alert-banner">
            <strong>{{ __('roles.security_protection') }}</strong> {!! __('roles.security_protection_super_admin', ['perms' => '<code>manage_roles</code>, <code>manage_users</code>, <code>view_dashboard</code>']) !!}
        </div>
    @endif

    <form action="{{ route('roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="description" class="form-label">{{ __('roles.description') }}</label>
            <textarea name="description" id="description" rows="2" class="form-control" placeholder="{{ __('roles.description_placeholder') }}">{{ old('description', $role->description) }}</textarea>
        </div>

        <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--ink);">{{ __('roles.permissions_matrix') }}</h3>
            <span style="font-size: 12px; color: var(--muted);">{{ __('roles.permissions_matrix_hint') }}</span>
        </div>

        @foreach ($groupedPermissions as $groupName => $permissions)
            <div class="group-box">
                <div class="group-header">
                    <h4>{{ $groupName }}</h4>
                    <span style="font-size: 11.5px; color: var(--muted);">{{ __('roles.options_count', ['count' => $permissions->count()]) }}</span>
                </div>
                <div class="perm-grid">
                    @foreach ($permissions as $permission)
                        @php
                            $isChecked = in_array($permission->id, old('permissions', $assignedPermissionIds));
                        @endphp
                        <label class="perm-item">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ $isChecked ? 'checked' : '' }}>
                            <div class="perm-info">
                                <strong>{{ $permission->name }}</strong>
                                <span>{{ $permission->description ?? __('roles.no_description') }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('roles.save_permission_changes') }}</button>
            <a href="{{ route('roles.show', $role) }}" class="btn btn-outline">{{ __('common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
