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

        @if($images && count($images))
        <div class="form-group mt-4">
            <label>Current Images</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach($customer->images as $index => $image)
                    <div class="position-relative" style="width: 150px;">
                        <img src="{{ asset('storage/' . $image) }}" class="img-thumbnail" />
                        <label class="text-danger" style="position: absolute; top: 5px; right: 10px; cursor: pointer;">
                            <input type="checkbox" name="delete_images[]" value="{{ $image }}" />
                            ✖
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <div class="form-group mt-4">
        <label for="images">Upload New Images</label>
        <input type="file" name="images[]" id="images" class="form-control" multiple>
    </div>
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
