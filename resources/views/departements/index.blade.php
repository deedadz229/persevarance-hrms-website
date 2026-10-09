@extends('layouts.app')

@section('title', 'Departments')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">

    <div>
        <h1>Departments</h1>
        <p style="color: #666; margin-top: 6px;">
            Kelola department perusahaan.
        </p>
    </div>

    <a href="{{ route('departments.create') }}"
       style="
            background: #222;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
       ">
        + Tambah Department
    </a>

</div>

@if(session('success'))
    <div style="
        background: #e8f5e9;
        color: #2e7d32;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 20px;
    ">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="
        background: #ffebee;
        color: #c62828;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 20px;
    ">
        {{ session('error') }}
    </div>
@endif

<div style="
    background: white;
    border-radius: 10px;
    padding: 20px;
">

    <table style="width: 100%; border-collapse: collapse;">

        <thead>
            <tr style="border-bottom: 1px solid #ddd; text-align: left;">
                <th style="padding: 12px;">No</th>
                <th style="padding: 12px;">Department</th>
                <th style="padding: 12px;">Description</th>
                <th style="padding: 12px;">Status</th>
                <th style="padding: 12px;">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($departments as $department)

                <tr style="border-bottom: 1px solid #eee;">

                    <td style="padding: 12px;">
                        {{ $loop->iteration }}
                    </td>

                    <td style="padding: 12px;">
                        {{ $department->name }}
                    </td>

                    <td style="padding: 12px;">
                        {{ $department->description ?? '-' }}
                    </td>

                    <td style="padding: 12px;">
                        {{ ucfirst($department->status) }}
                    </td>

                    <td style="padding: 12px;">

                        <a href="{{ route('departments.show', $department) }}">
                            Detail
                        </a>

                        |

                        <a href="{{ route('departments.edit', $department) }}">
                            Edit
                        </a>

                        |

                        <form
                            action="{{ route('departments.destroy', $department) }}"
                            method="POST"
                            style="display: inline;"
                            onsubmit="return confirm('Yakin ingin menghapus department ini?')"
                        >

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    style="
                                        border: none;
                                        background: none;
                                        color: red;
                                        cursor: pointer;
                                    ">
                                Hapus
                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="5" style="padding: 25px; text-align: center;">
                        Belum ada department.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

@endsection