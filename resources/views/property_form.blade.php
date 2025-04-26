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
                    <form action="{{ isset($property) ? url('/properties/' . $property->id) : url('/properties') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
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
