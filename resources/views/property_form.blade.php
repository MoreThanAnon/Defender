@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">{{ isset($property) ? 'Edit Property' : 'Create Property' }}</h3>
                    <form action="{{ isset($property) ? url('/properties/' . $property->id) : url('/properties') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($property))
                            @method('PUT')
                        @endif
                        <div class="form-group">
                            <label for="name">Property Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ $property->name ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price (€)</label>
                            <input type="number" id="price" name="price" class="form-control" value="{{ $property->price ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="beds">Beds</label>
                            <input type="number" id="beds" name="beds" class="form-control" value="{{ $property->beds ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="baths">Baths</label>
                            <input type="number" id="baths" name="baths" class="form-control" value="{{ $property->baths ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="size">Size (m²)</label>
                            <input type="number" id="size" name="size" class="form-control" value="{{ $property->size ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <input type="text" id="type" name="type" class="form-control" value="{{ $property->type ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="berRating">BER Rating</label>
                            <input type="text" id="berRating" name="berRating" class="form-control" value="{{ $property->berRating ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="2" required>{{ $property->address ?? '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required>{{ $property->description ?? '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="features">Features (comma-separated)</label>
                            <input type="text" id="features" name="features" class="form-control" value="{{ isset($property) ? implode(',', $property->features) : '' }}">
                        </div>
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" class="form-control" value="{{ $property->latitude ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" class="form-control" value="{{ $property->longitude ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="images">Property Images</label>
                            <input type="file" id="images" name="images[]" class="form-control" multiple>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ isset($property) ? 'Update Property' : 'Create Property' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
