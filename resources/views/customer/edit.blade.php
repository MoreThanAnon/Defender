@extends('layouts.app')

@section('content')
<div class="container">
    @include('components.navbar')
</div>

<div class="container mt-5">
    <h1>Edit Customer Information</h1>

    {{-- Success & Error Flash Messages --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>There were some problems with your input:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customer.edit') }}" method="POST">
        @csrf
        <input type="hidden" name="id" value="{{ $id }}">

        <div class="form-group">
            <label for="name">Name</label>
            <input
                type="text"
                id="real_name"
                name="real_name"
                class="form-control @error('real_name') is-invalid @enderror"
                value="{{ old('real_name', $customer->real_name) }}"
                required
            />
            @error('real_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $customer->email) }}"
                required
            />
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="address1">Address Line 1</label>
            <input
                type="text"
                id="address1"
                name="address1"
                class="form-control @error('address1') is-invalid @enderror"
                value="{{ old('address1', optional($address)->address1) }}"
            />
            @error('address1')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="address2">Address Line 2</label>
            <input
                type="text"
                id="address2"
                name="address2"
                class="form-control @error('address2') is-invalid @enderror"
                value="{{ old('address2', optional($address)->address2) }}"
            />
            @error('address2')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="address3">Address Line 3</label>
            <input
                type="text"
                id="address3"
                name="address3"
                class="form-control @error('address3') is-invalid @enderror"
                value="{{ old('address3', optional($address)->address3) }}"
            />
            @error('address3')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="town">Town</label>
            <input
                type="text"
                id="town"
                name="town"
                class="form-control @error('town') is-invalid @enderror"
                value="{{ old('town', optional($address)->city) }}"
            />
            @error('town')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="eircode">Eircode</label>
            <input
                type="text"
                id="eircode"
                name="eircode"
                class="form-control @error('eircode') is-invalid @enderror"
                value="{{ old('eircode', optional($address)->eircode) }}"
            />
            @error('eircode')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary mt-3">Update</button>
    </form>
</div>
@endsection
