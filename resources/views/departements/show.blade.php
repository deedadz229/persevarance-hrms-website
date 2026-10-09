@extends('layouts.app')

@section('title', 'Detail Department')

@section('content')

<h1 style="margin-bottom: 25px;">
    Detail Department
</h1>

<div style="
    background: white;
    padding: 25px;
    border-radius: 10px;
    max-width: 700px;
">

    <p>
        <strong>Nama:</strong>
        {{ $department->name }}
    </p>

    <p style="margin-top: 15px;">
        <strong>Description:</strong>
        {{ $department->description ?? '-' }}
    </p>

    <p style="margin-top: 15px;">
        <strong>Status:</strong>
        {{ ucfirst($department->status) }}
    </p>

    <p style="margin-top: 15px;">
        <strong>Dibuat:</strong>
        {{ $department->created_at->format('d-m-Y H:i') }}
    </p>

    <div style="margin-top: 25px;">

        <a href="{{ route('departments.edit', $department) }}">
            Edit
        </a>

        <a
            href="{{ route('departments.index') }}"
            style="margin-left: 15px;"
        >
            Kembali
        </a>

    </div>

</div>

@endsection