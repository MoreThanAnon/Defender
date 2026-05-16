@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    @php
                        $isEditing = isset($property) && $property->exists;
                        $features = $property->features ?? '';
                        $featuresValue = is_array($features) ? implode(',', $features) : $features;
                    @endphp

                    <h3 class="card-title">{{ $isEditing ? 'Edit Property' : 'Create Property' }}</h3>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ $isEditing ? route('properties.update', $property->id) : route('property.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if($isEditing)
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label for="name">Property Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $property->name ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Price (&euro;)</label>
                            <input type="number" id="price" name="price" class="form-control" value="{{ old('price', $property->price ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="beds">Beds</label>
                            <input type="number" id="beds" name="beds" class="form-control" value="{{ old('beds', $property->beds ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="baths">Baths</label>
                            <input type="number" id="baths" name="baths" class="form-control" value="{{ old('baths', $property->baths ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="size">Size (m&sup2;)</label>
                            <input type="number" id="size" name="size" class="form-control" value="{{ old('size', $property->size ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="type">Type</label>
                            <input type="text" id="type" name="type" class="form-control" value="{{ old('type', $property->type ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="ber_rating">BER Rating</label>
                            <input type="text" id="ber_rating" name="ber_rating" class="form-control" value="{{ old('ber_rating', $property->ber_rating ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="2" required>{{ old('address', $property->address ?? '') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="eircode">Eircode</label>
                            <input type="text" value="{{ old('eircode', $property->eircode ?? '') }}" name="eircode" id="eircode" class="form-control" maxlength="20" placeholder="Enter eircode">
                            <small id="eircodeError" class="text-danger" style="display: none;">Invalid Eircode format. Example: A65 F4E2</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required>{{ old('description', $property->description ?? '') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="features">Features (comma-separated)</label>
                            <input type="text" id="features" name="features" class="form-control" value="{{ old('features', $featuresValue) }}">
                        </div>

                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" class="form-control" value="{{ old('latitude', $property->latitude ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" class="form-control" value="{{ old('longitude', $property->longitude ?? '') }}" required>
                        </div>

                        <div class="form-group mt-4">
                            <label>Current Cover Image</label>
                            @if(isset($property->cover_image) && $property->cover_image)
                                <div class="position-relative" style="width: 150px;">
                                    <img src="{{ asset('storage/' . $property->cover_image) }}" class="img-thumbnail" />
                                    <label class="text-danger" style="position: absolute; top: 5px; right: 10px; cursor: pointer;">
                                        <input type="checkbox" name="delete_cover_image" value="1" />
                                        &times;
                                    </label>
                                </div>
                            @else
                                <p>No cover image uploaded.</p>
                            @endif
                        </div>

                        <div class="form-group mt-4">
                            <label for="cover_image">Upload New Cover Image</label>
                            <input type="file" name="cover_image" id="cover_image" class="form-control">
                        </div>

                        <hr><br><hr>

                        @if($images && count($images))
                            <div class="form-group mt-4">
                                <label>Current Images</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($images as $image)
                                        <div class="position-relative" style="width: 150px;">
                                            <img src="{{ asset('storage/' . $image->path) }}" class="img-thumbnail" />
                                            <label class="text-danger" style="position: absolute; top: 5px; right: 10px; cursor: pointer;">
                                                <input type="checkbox" name="delete_images[]" value="{{ $image->path }}" />
                                                &times;
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

                        <div class="form-group mt-4">
                            <label for="campaign_start">Campaign Start Date</label>
                            <input type="date" id="campaign_start" name="campaign_start" class="form-control"
                                   value="{{ old('campaign_start', isset($property->campaign_start) ? \Carbon\Carbon::parse($property->campaign_start)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="form-group mt-4">
                            <label for="campaign_end">Campaign End Date</label>
                            <input type="date" id="campaign_end" name="campaign_end" class="form-control"
                                   value="{{ old('campaign_end', isset($property->campaign_end) ? \Carbon\Carbon::parse($property->campaign_end)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="form-group mt-4">
                            <label for="internal_days">Internal Days</label>
                            <input type="number" id="internal_days" name="internal_days" class="form-control"
                                   value="{{ old('internal_days', $property->internal_days ?? 0) }}" min="0">
                        </div>

                        <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Update Property' : 'Create Property' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('eircode').addEventListener('input', function () {
        const eircodePattern = /^[A-Za-z0-9]{3}[ ]?[A-Za-z0-9]{4}$/;
        const eircodeError = document.getElementById('eircodeError');

        if (this.value && !eircodePattern.test(this.value)) {
            eircodeError.style.display = 'block';
        } else {
            eircodeError.style.display = 'none';
        }
    });
</script>
@endsection
