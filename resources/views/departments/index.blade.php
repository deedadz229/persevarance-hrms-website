@extends('layouts.app')

@section('title', 'Manajemen Departemen')

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
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 180px; }
    .filter-group.search-group { flex: 2; min-width: 240px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .table-container { overflow-x: auto; }
    .table-dept { width: 100%; border-collapse: collapse; text-align: left; }
    .table-dept th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-dept td { padding: 14px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-dept tbody tr:hover { background: #fafcfc; }
    .dept-name { font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .badge-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-active { background: #e6f7f2; color: #0a7b6f; }
    .badge-inactive { background: #f1f3f5; color: #5f6c77; }
    .badge-count { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #f0f4f7; color: var(--ink); }
    .action-links { display: flex; align-items: center; gap: 8px; }
    .action-link { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
    .action-link-danger { color: #dc2626; border-color: #fee2e2; }
    .action-link-danger:hover { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }
    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Manajemen Departemen</h2>
        <p>Kelola struktur divisi dan departemen operasional perusahaan</p>
    </div>
    <div class="header-actions">
        @if (auth()->user()->role?->name === 'super_admin')
            <a href="{{ route('departments.create') }}" class="btn btn-primary">
                <span>+</span> Tambah Department
            </a>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="flash flash-success" role="alert">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="flash flash-error" role="alert">
        {{ session('error') }}
    </div>
@endif

<div class="card filter-card">
    <form action="{{ route('departments.index') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama atau deskripsi departemen...">
        </div>
        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-outline">Filter</button>
        @if ($search || $status)
            <a href="{{ route('departments.index') }}" class="btn btn-outline" style="color: var(--muted);">Reset</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-dept">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Department Name</th>
                    <th>Description</th>
                    <th style="width: 160px;">Jumlah Karyawan</th>
                    <th style="width: 120px;">Status</th>
                    <th style="text-align: right; width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $dept)
                    <tr>
                        <td>{{ $departments->firstItem() ? $departments->firstItem() + $loop->index : $loop->iteration }}</td>
                        <td>
                            <span class="dept-name">{{ $dept->name }}</span>
                        </td>
                        <td>{{ $dept->description ?: '-' }}</td>
                        <td>
                            <span class="badge-count">
                                <span>👥</span> {{ $dept->employees_count }} orang
                            </span>
                        </td>
                        <td>
                            @if ($dept->status === 'active')
                                <span class="badge-status badge-active">Active</span>
                            @else
                                <span class="badge-status badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div class="action-links" style="justify-content: flex-end;">
                                <a href="{{ route('departments.show', $dept) }}" class="action-link" title="Detail Department">Detail</a>
                                @if (auth()->user()->role?->name === 'super_admin')
                                    <a href="{{ route('departments.edit', $dept) }}" class="action-link" title="Edit Department">Edit</a>
                                    <form action="{{ route('departments.destroy', $dept) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus department {{ $dept->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-link action-link-danger" style="cursor: pointer;" title="Hapus Department">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <p style="margin: 0; font-size: 14px; font-weight: 600;">Belum ada data department</p>
                                <span style="font-size: 12px; margin-top: 4px; display: block;">Silakan tambahkan department baru atau ubah kriteria pencarian.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($departments->hasPages())
        <div class="pagination-wrapper">
            <div>
                Menampilkan {{ $departments->firstItem() }} - {{ $departments->lastItem() }} dari {{ $departments->total() }} department
            </div>
            <div>
                {{ $departments->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
