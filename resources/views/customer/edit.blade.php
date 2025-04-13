@extends('layouts.app')

@section('content')
<div class="container"> <!-- Add container to limit width -->
    @include('partials.navbar') <!-- Add the navbar here -->
</div>
<div class="container mt-5">
    <h1>Edit Customer Information</h1>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('customer.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Name</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-control"
                value="{{ old('name', auth()->user()->name) }}"
                required
            />
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                value="{{ old('email', auth()->user()->email) }}"
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
                value="{{ old('address1', auth()->user()->address1) }}"
            />
        </div>
        <div class="form-group">
            <label for="address2">Address Line 2</label>
            <input
                type="text"
                id="address2"
                name="address2"
                class="form-control"
                value="{{ old('address2', auth()->user()->address2) }}"
            />
        </div>
        <div class="form-group">
            <label for="address3">Address Line 3</label>
            <input
                type="text"
                id="address3"
                name="address3"
                class="form-control"
                value="{{ old('address3', auth()->user()->address3) }}"
            />
        </div>
        <div class="form-group">
            <label for="town">Town</label>
            <input
                type="text"
                id="town"
                name="town"
                class="form-control"
                value="{{ old('town', auth()->user()->town) }}"
            />
        </div>
        <div class="form-group">
            <label for="eircode">Eircode</label>
            <input
                type="text"
                id="eircode"
                name="eircode"
                class="form-control"
                value="{{ old('eircode', auth()->user()->eircode) }}"
            />
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>

    <form action="{{ route('properties.update', $id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Property fields -->
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $property->name) }}" required>
        </div>

        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" id="price" name="price" class="form-control" value="{{ old('price', $property->price) }}" required>
        </div>

        <!-- Image upload -->
        <div class="form-group">
            <label for="images">Upload Images</label>
            <input type="file" id="images" name="images[]" class="form-control" multiple>
            <small class="form-text text-muted">You can upload multiple images.</small>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
