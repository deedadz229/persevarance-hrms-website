@extends('layouts.app')

@section('title', 'Role & Permission')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .table-container { overflow-x: auto; }
    .table-roles { width: 100%; border-collapse: collapse; text-align: left; }
    .table-roles th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-roles td { padding: 16px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-roles tbody tr:hover { background: #fafcfc; }
    .badge-count { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 12px; font-weight: 700; background: var(--brand-soft); color: var(--brand-dark); }
    .badge-users { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #f1f3f5; color: #495057; }
    .action-links { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
    .action-link { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; transition: all .15s ease; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
    .action-link-primary { background: var(--brand); color: #fff; border-color: var(--brand); }
    .action-link-primary:hover { background: var(--brand-dark); color: #fff; border-color: var(--brand-dark); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('roles.title') }}</h2>
        <p>{{ __('roles.subtitle') }}</p>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-roles">
            <thead>
                <tr>
                    <th>{{ __('roles.role') }}</th>
                    <th>{{ __('roles.slug') }}</th>
                    <th>{{ __('roles.description') }}</th>
                    <th>{{ __('roles.users_count') }}</th>
                    <th>{{ __('roles.permissions_count') }}</th>
                    <th style="text-align: right;">{{ __('common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td>
                            <strong>{{ $role->label }}</strong>
                        </td>
                        <td>
                            <code style="font-size: 12px; background: #f0f4f7; padding: 2px 6px; border-radius: 4px;">{{ $role->name }}</code>
                        </td>
                        <td style="color: var(--muted); max-width: 300px;">
                            {{ $role->description ?? __('roles.no_description') }}
                        </td>
                        <td>
                            <span class="badge-users">
                                {{ __('roles.users_suffix', ['count' => $role->users_count]) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-count">
                                {{ __('roles.active_perms_suffix', ['count' => $role->permissions_count]) }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-links">
                                <a href="{{ route('roles.show', $role) }}" class="action-link">{{ __('common.detail') }}</a>
                                <a href="{{ route('roles.edit', $role) }}" class="action-link action-link-primary">{{ __('roles.assign_permissions') }}</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
