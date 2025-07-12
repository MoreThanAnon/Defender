@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">{{ isset($property) ? 'Edit Property' : 'Create Property' }}</h3>
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ isset($property->id) ? route('properties.update', $property->id) : route('property.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($property->id))
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
                            <input type="text" id="berRating" name="berRating" class="form-control" value="{{ $property->ber_rating ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="2" required>{{ $property->address ?? '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="eircode">Eircode</label>
                            <input type="text" value="{{ $property->eircode ?? '' }}" name="eircode" id="eircode" class="form-control" maxlength="20" placeholder="Enter eircode">
                            <small id="eircodeError" class="text-danger" style="display: none;">Invalid Eircode format. Example: A65 F4E2</small>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required>{{ $property->description ?? '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="features">Features (comma-separated)</label>
                            <input type="text" id="features" name="features" class="form-control" 
                                   value="{{ isset($property) ? (is_array($property->features) ? implode(',', $property->features) : $property->features) : '' }}">
                        </div>
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" class="form-control" value="{{ $property->latitude ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" class="form-control" value="{{ $property->longitude ?? '' }}" required>
                        </div>
                        <div class="form-group mt-4">
                            <label>Current Cover Image</label>
                            @if(isset($property->cover_image) && $property->cover_image)
                                <div class="position-relative" style="width: 150px;">
                                    <img src="{{ asset('storage/' . $property->cover_image) }}" class="img-thumbnail" />
                                    <label class="text-danger" style="position: absolute; top: 5px; right: 10px; cursor: pointer;">
                                        <input type="checkbox" name="delete_cover_image" value="1" />
                                        ✖
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

                    <div class="form-group mt-4">
                        <label for="campaign_start">Campaign Start Date</label>
                        <input type="date" id="campaign_start" name="campaign_start" class="form-control"
                               value="{{ isset($property->campaign_start) ? \Carbon\Carbon::parse($property->campaign_start)->format('Y-m-d') : '' }}">
                    </div>

                    <div class="form-group mt-4">
                        <label for="campaign_end">Campaign End Date</label>
                        <input type="date" id="campaign_end" name="campaign_end" class="form-control"
                               value="{{ isset($property->campaign_end) ? \Carbon\Carbon::parse($property->campaign_end)->format('Y-m-d') : '' }}">
                    </div>

                    <div class="form-group mt-4">
                        <label for="internal_days">Internal Days</label>
                        <input type="number" id="internal_days" name="internal_days" class="form-control"
                               value="{{ $property->internal_days ?? 0 }}" min="0">
                    </div>

                        <button type="submit" class="btn btn-primary">{{ isset($property) ? 'Update Property' : 'Create Property' }}</button>
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
