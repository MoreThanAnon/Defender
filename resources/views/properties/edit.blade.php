@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="card-title">Edit Property</h3>

                    <div id="propertyImagesCarousel" class="carousel slide mb-4" data-ride="carousel">
                        <div class="carousel-inner">
                            @if(isset($images) && count($images) > 0)
                                @foreach($images as $index => $image)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        <img src="{{ asset('storage/' . $image->path) }}" class="d-block w-100" alt="Property Image">
                                    </div>
                                @endforeach
                            @else
                                <div class="carousel-item active">
                                    <img src="{{ asset('storage/default-placeholder.png') }}" class="d-block w-100" alt="No Images Available">
                                </div>
                            @endif
                        </div>
                        <a class="carousel-control-prev" href="#propertyImagesCarousel" role="button" data-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#propertyImagesCarousel" role="button" data-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>

                    <form action="{{ route('properties.update', $property->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Property Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $property->name) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" name="price" id="price" class="form-control" value="{{ old('price', $property->price) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="beds">Beds</label>
                            <input type="number" name="beds" id="beds" class="form-control" value="{{ old('beds', $property->beds) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="baths">Baths</label>
                            <input type="number" name="baths" id="baths" class="form-control" value="{{ old('baths', $property->baths) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="size">Size (m²)</label>
                            <input type="number" name="size" id="size" class="form-control" value="{{ old('size', $property->size) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="type">Type</label>
                            <input type="text" name="type" id="type" class="form-control" value="{{ old('type', $property->type) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="berRating">BER Rating</label>
                            <input type="text" name="berRating" id="berRating" class="form-control" value="{{ old('berRating', $property->ber_rating) }}">
                        </div>

                        <div class="form-group">
                            <label for="eircode">Eircode</label>
                            <input type="text" name="eircode" id="eircode" class="form-control" value="{{ old('eircode', $property->eircode) }}" maxlength="10">
                        </div>

                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="text" name="latitude" id="latitude" class="form-control" value="{{ old('latitude', $property->latitude) }}" readonly>
                        </div>

                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="text" name="longitude" id="longitude" class="form-control" value="{{ old('longitude', $property->longitude) }}" readonly>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" name="address" id="address" class="form-control" value="{{ old('address', $property->address) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $property->description) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="saleType">Sale Type</label>
                            <input type="text" name="saleType" id="saleType" class="form-control" value="{{ old('saleType', $property->saleType) }}">
                        </div>

                        <div class="form-group">
                            <label for="stampDuty">Stamp Duty</label>
                            <input type="number" name="stampDuty" id="stampDuty" class="form-control" value="{{ old('stampDuty', $property->stampDuty) }}">
                        </div>

                        <div class="form-group">
                            <label for="features">Features</label>
                            <textarea name="features" id="features" class="form-control" rows="3">{{ old('features', implode(',', (array) $property->features)) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="auctioneerName">Auctioneer Name</label>
                            <input type="text" name="auctioneerName" id="auctioneerName" class="form-control" value="{{ old('auctioneerName', $property->auctioneerName) }}">
                        </div>

                        <div class="form-group">
                            <label for="images">Upload Images</label>
                            <input type="file" name="images[]" id="images" class="form-control" multiple>
                            <small class="form-text text-muted">You can upload multiple images.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Property</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('eircode').addEventListener('blur', function () {
        const eircode = this.value.trim();
        if (eircode) {
            fetch(`/api/eircode/${eircode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        document.getElementById('latitude').value = data.latitude;
                        document.getElementById('longitude').value = data.longitude;
                    } else {
                        alert('Eircode not found or invalid.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching Eircode data:', error);
                    alert('An error occurred while fetching Eircode data.');
                });
        }
    });

    
</script>
<script>
    // Send PHP data to JavaScript and log it
    const customerData = @json($customer);
    const imagesData = @json($images);
    alert("test");
    console.log('Customer:', customerData);
    console.log('Images:', imagesData);
</script>
@endsection