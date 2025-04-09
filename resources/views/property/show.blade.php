@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Property Images Carousel -->
            <div id="propertyImagesCarousel" class="carousel slide mb-4" data-ride="carousel">
                <div class="carousel-inner">
                    @foreach($property->images as $index => $image)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <img src="{{ asset('storage/' . $image) }}" class="d-block w-100" alt="Property Image">
                        </div>
                    @endforeach
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

            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h3 class="card-title">{{ $property->name }}</h3>
                    <p class="text-muted">€{{ number_format($property->price) }}</p>
                    <p class="card-text">
                        <strong>{{ $property->beds }}</strong> Beds • 
                        <strong>{{ $property->baths }}</strong> Bath • 
                        <strong>{{ $property->size }}</strong> m² • 
                        {{ ucfirst($property->type) }}
                    </p>
                    <p class="card-text">BER Rating: <strong>{{ $property->berRating }}</strong></p>

                    <hr>
                    <p class="card-text"><em>{{ $property->address }}</em></p>
                    <p class="card-text">{{ $property->description }}</p>

                    <!-- Property Features -->
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Sale Type:</strong> {{ $property->saleType }}</li>
                        <li class="list-group-item"><strong>Stamp Duty:</strong> €{{ number_format($property->stampDuty) }}</li>
                        <li class="list-group-item"><strong>Features:</strong>
                            <ul>
                                <li>Connected to water and electricity</li>
                                <li>Open-plan kitchen and living space</li>
                                <li>Character and charm</li>
                                <li>Views of the Slieve Bloom Mountains</li>
                            </ul>
                        </li>
                    </ul>

                    <!-- Place a Bid Form -->
                    <form action="{{ url('/properties/' . $property->id . '/bid') }}" method="POST" class="mt-4">
                        @csrf
                        <div class="form-group">
                            <label for="bidAmount" class="font-weight-bold">Place a Bid:</label>
                            <input type="number" id="bidAmount" name="bidAmount" class="form-control" required placeholder="Enter your bid amount">
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Submit Bid</button>
                    </form>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5>Contact Information</h5>
                    <p>Auctioneer: {{ $property->auctioneerName }}</p>
                    <form action="{{ url('/properties/' . $property->id . '/contact') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="message">Your Inquiry:</label>
                            <textarea id="message" name="message" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-info">Send Inquiry</button>
                    </form>
                </div>
            </div>

            <!-- Map Section -->
            <div id="map" class="mt-4" style="height: 400px;"></div>
            <script>
                function initMap() {
                    var propertyLocation = { lat: {{ $property->latitude }}, lng: {{ $property->longitude }} };
                    var map = new google.maps.Map(document.getElementById('map'), { zoom: 15, center: propertyLocation });
                    new google.maps.Marker({ position: propertyLocation, map: map });
                }
            </script>
            <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap"></script>
        </div>
    </div>
</div>
@endsection

<!-- CSS Enhancements -->
<style>
.card {
    border-radius: 10px;
}
.card-title {
    font-weight: bold;
    color: #333;
}
.carousel img {
    border-radius: 10px;
}
.list-group-item {
    font-size: 1.1rem;
}
</style>