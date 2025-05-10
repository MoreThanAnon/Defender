@extends('layouts.app')

@section('content')
<div class="container">
    @include('components.navbar')
</div>

<div class="container mt-5">
    <h1>Edit Customer Information</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('customer.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                value="{{ old('email', $customer->email) }}"
                required
            />
        </div>

        <div class="form-group">
            <label for="address1">Address Line 1</label>
            <input
                type="text"
                id="address1"
                name="address1"
                class="form-control"
                value="{{ old('address1', $customer->address1) }}"
            />
        </div>

        <div class="form-group">
            <label for="address2">Address Line 2</label>
            <input
                type="text"
                id="address2"
                name="address2"
                class="form-control"
                value="{{ old('address2', $customer->address2) }}"
            />
        </div>

        <div class="form-group">
            <label for="address3">Address Line 3</label>
            <input
                type="text"
                id="address3"
                name="address3"
                class="form-control"
                value="{{ old('address3', $customer->address3) }}"
            />
        </div>

        <div class="form-group">
            <label for="town">Town</label>
            <input
                type="text"
                id="town"
                name="town"
                class="form-control"
                value="{{ old('town', $customer->town) }}"
            />
        </div>

        <div class="form-group">
            <label for="eircode">Eircode</label>
            <input
                type="text"
                id="eircode"
                name="eircode"
                class="form-control"
                value="{{ old('eircode', $customer->eircode) }}"
            />
        </div>

        <button type="submit" class="btn btn-primary mt-3">Update</button>
    </form>
</div>


@endsection
