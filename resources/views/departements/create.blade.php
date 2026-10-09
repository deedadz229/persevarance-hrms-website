@extends('layouts.app')

@section('title', 'Tambah Department')

@section('content')

<h1 style="margin-bottom: 25px;">
    Tambah Department
</h1>

<div style="
    background: white;
    padding: 25px;
    border-radius: 10px;
    max-width: 700px;
">

    <form method="POST" action="{{ route('departments.store') }}">

        @csrf

        <div style="margin-bottom: 18px;">

            <label>Nama Department</label>

            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                style="width: 100%; padding: 10px; margin-top: 7px;"
            >

            @error('name')
                <small style="color: red;">
                    {{ $message }}
                </small>
            @enderror

        </div>

        <div style="margin-bottom: 18px;">

            <label>Description</label>

            <textarea
                name="description"
                rows="4"
                style="width: 100%; padding: 10px; margin-top: 7px;"
            >{{ old('description') }}</textarea>

        </div>

        <div style="margin-bottom: 20px;">

            <label>Status</label>

            <select
                name="status"
                style="width: 100%; padding: 10px; margin-top: 7px;"
            >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

        </div>

        <button
            type="submit"
            style="
                background: #222;
                color: white;
                border: none;
                padding: 11px 18px;
                border-radius: 6px;
                cursor: pointer;
            "
        >
            Simpan
        </button>

        <a
            href="{{ route('departments.index') }}"
            style="margin-left: 10px;"
        >
            Batal
        </a>

    </form>

</div>

@endsection