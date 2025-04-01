@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Property</h1>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('property.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Property Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" name="price" id="price" class="form-control" value="{{ old('price') }}" required>
        </div>
        <div class="form-group">
            <label for="beds">Beds</label>
            <input type="number" name="beds" id="beds" class="form-control" value="{{ old('beds') }}" required>
        </div>
        <div class="form-group">
            <label for="baths">Baths</label>
            <input type="number" name="baths" id="baths" class="form-control" value="{{ old('baths') }}" required>
        </div>
        <div class="form-group">
            <label for="size">Size (sq ft)</label>
            <input type="number" name="size" id="size" class="form-control" value="{{ old('size') }}" required>
        </div>
        <div class="form-group">
            <label for="type">Type</label>
            <input type="text" name="type" id="type" class="form-control" value="{{ old('type') }}" required>
        </div>
        <div class="form-group">
            <label for="ber_rating">BER Rating</label>
            <input type="text" name="ber_rating" id="ber_rating" class="form-control" value="{{ old('ber_rating') }}">
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea name="address" id="address" class="form-control" required>{{ old('address') }}</textarea>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
        </div>
        <div class="form-group">
            <label for="sale_type">Sale Type</label>
            <input type="text" name="sale_type" id="sale_type" class="form-control" value="{{ old('sale_type') }}" required>
        </div>
        <div class="form-group">
            <label for="stamp_duty">Stamp Duty</label>
            <input type="number" name="stamp_duty" id="stamp_duty" class="form-control" value="{{ old('stamp_duty') }}">
        </div>
        <div class="form-group">
            <label for="features">Features</label>
            <textarea name="features[]" id="features" class="form-control">{{ old('features.0') }}</textarea>
        </div>
        <div class="form-group">
            <label for="auctioneer_name">Auctioneer Name</label>
            <input type="text" name="auctioneer_name" id="auctioneer_name" class="form-control" value="{{ old('auctioneer_name') }}">
        </div>
        <div class="form-group">
            <label for="latitude">Latitude</label>
            <input type="number" step="any" name="latitude" id="latitude" class="form-control" value="{{ old('latitude') }}">
        </div>
        <div class="form-group">
            <label for="longitude">Longitude</label>
            <input type="number" step="any" name="longitude" id="longitude" class="form-control" value="{{ old('longitude') }}">
        </div>
        <div class="form-group">
            <label for="images">Images</label>
            <input type="file" name="images[]" id="images" class="form-control" multiple>
        </div>
        <button type="submit" class="btn btn-primary">Add Property</button>
    </form>
</div>
@endsection
