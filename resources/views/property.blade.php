@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Property Images Carousel -->
            <div id="propertyImagesCarousel" class="carousel slide mb-4" data-ride="carousel">
                <div class="carousel-inner">
                    @if(isset($images) && count($images) > 0)
                        @foreach($images as $index => $image)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <img src="{{ asset('storage/' . $image) }}" class="d-block w-100" alt="Property Image">
                            </div>
                        @endforeach
                    @else
                        <div class="carousel-item active">
                            <img src="{{ asset('images/placeholder.jpg') }}" class="d-block w-100" alt="Placeholder Image">
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

            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h3 class="card-title">{{ $name }}</h3>
                    <p class="text-muted">€{{ number_format($price) }}</p>
                    <p class="card-text">
                        <strong>{{ $beds }}</strong> Bed • 
                        <strong>{{ $baths }}</strong> Bath • 
                        <strong>{{ $size }}</strong> m² • 
                        {{ ucfirst($type) }}
                    </p>
                    <p class="card-text">BER Rating: <strong>{{ $berRating }}</strong></p>
                    <hr>
                    <p class="card-text"><em>{{ $address }}</em></p>
                    <p class="card-text">{{ $description }}</p>

                    <!-- Edit Property Button -->
                    <a href="{{ url('/properties/' . $id . '/edit') }}" class="btn btn-warning mt-3">Edit Property</a>

                    <!-- Property Features -->
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Sale Type:</strong> {{ $saleType }}</li>
                        <li class="list-group-item"><strong>Stamp Duty:</strong> €{{ number_format($stampDuty) }}</li>
                        <li class="list-group-item"><strong>Features:</strong>
                            <ul>
                                @foreach($features as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        </li>
                    </ul>

                    <!-- Place a Bid Form -->
                    <form action="{{ url('/properties/' . $id . '/bid') }}" method="POST" class="mt-4">
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
                    <p>Auctioneer: {{ $auctioneerName }}</p>
                    <form action="{{ url('/properties/' . $id . '/contact') }}" method="POST">
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
                    var propertyLocation = { lat: {{ $latitude }}, lng: {{ $longitude }} };
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