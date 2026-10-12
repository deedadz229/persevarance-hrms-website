@extends('layouts.app')

@section('title', __('users.title'))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; }
    .filter-group { flex: 1; min-width: 180px; }
    .filter-group.search-group { flex: 2; min-width: 240px; }
    .filter-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .table-container { overflow-x: auto; }
    .table-users { width: 100%; border-collapse: collapse; text-align: left; }
    .table-users th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-users td { padding: 14px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-users tbody tr:hover { background: #fafcfc; }
    .user-info { display: flex; align-items: center; gap: 12px; }
    .user-avatar { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; background: var(--brand-soft); color: var(--brand-dark); font-weight: 700; font-size: 13px; flex-shrink: 0; }
    .user-meta strong { display: block; font-weight: 600; color: var(--ink); }
    .user-meta span { display: block; margin-top: 1px; color: var(--muted); font-size: 12px; }
    .badge-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-active { background: #e6f7f2; color: #0a7b6f; }
    .badge-inactive { background: #feebeb; color: #b92e2e; }
    .badge-role { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 600; background: #f0f4f7; color: #2d3748; }
    .role-super { background: #f3f0ff; color: #6741d9; border: 1px solid #d0bfff; }
    .role-hrd { background: #e8f4fd; color: #1971c2; border: 1px solid #a5d8ff; }
    .role-taiwan { background: #fff4e6; color: #d9480f; border: 1px solid #ffd8a8; }
    .action-links { display: flex; align-items: center; gap: 6px; }
    .action-link { padding: 5px 9px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; cursor: pointer; transition: all .15s ease; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
    .btn-toggle-deactivate { color: #b92e2e; border-color: #f3caca; }
    .btn-toggle-deactivate:hover { background: #feebeb; border-color: #b92e2e; color: #9c1414; }
    .btn-toggle-activate { color: #0a7b6f; border-color: #bce6d9; }
    .btn-toggle-activate:hover { background: #e6f7f2; border-color: #0a7b6f; color: #06544c; }
    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('users.title') }}</h2>
        <p>{{ __('users.subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <span>+</span> {{ __('users.add_user') }}
        </a>
    </div>
</div>

<div class="card filter-card">
    <form action="{{ route('users.index') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <label for="search">{{ __('common.search') }}</label>
            <input type="text" id="search" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('users.search_placeholder') }}">
        </div>
        <div class="filter-group">
            <label for="role">{{ __('users.role') }}</label>
            <select id="role" name="role" class="form-control">
                <option value="">{{ __('users.all_roles') }}</option>
                @foreach ($roles as $r)
                    <option value="{{ $r->name }}" {{ $role === $r->name ? 'selected' : '' }}>
                        {{ $r->label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="status">{{ __('common.status') }}</label>
            <select id="status" name="status" class="form-control">
                <option value="">{{ __('users.all_status') }}</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">{{ __('common.filter') }}</button>
            @if ($search || $role || $status)
                <a href="{{ route('users.index') }}" class="btn btn-outline" style="margin-left: 4px;">{{ __('common.reset') }}</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-users">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>{{ __('users.user_name') }}</th>
                    <th>{{ __('users.role') }}</th>
                    <th>{{ __('users.linked_employee') }}</th>
                    <th style="width: 120px;">{{ __('common.status') }}</th>
                    <th>{{ __('users.last_login') }}</th>
                    <th style="text-align: right; width: 220px;">{{ __('common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $users->firstItem() ? $users->firstItem() + $loop->index : $loop->iteration }}</td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="user-meta">
                                    <strong>{{ $user->name }}</strong>
                                    <span>{{ $user->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $roleClass = match($user->role?->name) {
                                    'super_admin' => 'role-super',
                                    'manager_hrd' => 'role-hrd',
                                    'taiwan_account' => 'role-taiwan',
                                    default => ''
                                };
                            @endphp
                            <span class="badge-role {{ $roleClass }}">
                                {{ $user->role?->label ?? 'User' }}
                            </span>
                        </td>
                        <td>
                            @if ($user->employee)
                                <a href="{{ route('employees.show', $user->employee) }}" style="color: var(--brand); text-decoration: none; font-weight: 500;">
                                    {{ $user->employee->name }}
                                </a>
                                <span style="display: block; font-size: 11.5px; color: var(--muted);">ID: {{ $user->employee->employee_code }}</span>
                            @else
                                <span style="color: var(--muted); font-size: 12px;">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge-status badge-active">{{ __('users.active') }}</span>
                            @else
                                <span class="badge-status badge-inactive">{{ __('users.inactive') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->last_login_at)
                                <span style="font-size: 12.5px; color: var(--ink);">{{ $user->last_login_at->format('d M Y, H:i') }}</span>
                                @if ($user->last_login_ip)
                                    <span style="display: block; font-size: 11px; color: var(--muted); font-family: monospace;">IP: {{ $user->last_login_ip }}</span>
                                @endif
                            @else
                                <span style="color: var(--muted); font-size: 12px;">{{ __('users.never_login') }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div class="action-links" style="justify-content: flex-end;">
                                <a href="{{ route('users.show', $user) }}" class="action-link" title="{{ __('common.detail') }}">{{ __('common.detail') }}</a>
                                <a href="{{ route('users.edit', $user) }}" class="action-link" title="{{ __('common.edit') }}">{{ __('common.edit') }}</a>
                                
                                @if ($user->id !== auth()->id())
                                    <form action="{{ route('users.toggle-status', $user) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ $user->is_active ? __('users.confirm_deactivate', ['name' => $user->name]) : __('users.confirm_activate', ['name' => $user->name]) }}');">
                                        @csrf
                                        @if ($user->is_active)
                                            <button type="submit" class="action-link btn-toggle-deactivate" title="{{ __('users.deactivate') }}">{{ __('users.deactivate') }}</button>
                                        @else
                                            <button type="submit" class="action-link btn-toggle-activate" title="{{ __('users.activate') }}">{{ __('users.activate') }}</button>
                                        @endif
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <p style="margin: 0; font-size: 14px; font-weight: 600;">{{ __('users.no_users') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="pagination-wrapper">
            <div>
                {{ __('users.showing_records', ['from' => $users->firstItem(), 'to' => $users->lastItem(), 'total' => $users->total()]) }}
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
