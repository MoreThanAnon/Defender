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

                    @if(auth()->check() && in_array(auth()->user()->role, ['moderator', 'admin']))
                        <!-- Edit Property Button -->
                        <a href="{{ route('properties.edit', $id) }}" class="btn btn-warning mt-3">Edit Property</a>
                    @endif

                    @if(auth()->check())
                        <p class="text-muted">User Role: {{ auth()->user()->role }}</p>
                    @endif

                    <!-- Property Features -->
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Sale Type:</strong> {{ $saleType }}</li>
                        <li class="list-group-item"><strong>Stamp Duty:</strong> €{{ number_format($stampDuty) }}</li>
                        <li class="list-group-item"><strong>Features:</strong>
                            <ul>
                                @if(isset($features) && is_array($features))
                                    @foreach($features as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                @endif
                            </ul>
                        </li>
                    </ul>

                    @if(auth()->check() && auth()->user()->role === 'user')
                        <!-- Place a Bid Form -->
                        <form action="{{ url('/properties/' . $id . '/bid') }}" method="POST" class="mt-4">
                            @csrf
                            <div class="form-group">
                                <label for="bidAmount" class="font-weight-bold">Place a Bid:</label>
                                <input type="number" id="bidAmount" name="bidAmount" class="form-control" required placeholder="Enter your bid amount">
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Submit Bid</button>
                        </form>
                    @endif
                </div>
            </div>

            @if(auth()->check() && auth()->user()->role === 'user')
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
            @endif

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
        <div class="col-lg-4">
            <!-- Collective Fund Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Contribute to the Collective Fund</h5>

                    <p class="text-muted">Current Fund: €{{ number_format($collectiveFund, 2) }}</p>
                    <p class="text-muted">Goal: €{{ number_format($fundGoal, 2) }}</p>
                    <p class="text-muted">Percentage Raised: {{ round(($collectiveFund / $fundGoal) * 100, 2) }}%</p>
                    <button type="button" class="btn btn-primary mt-3" data-toggle="modal" data-target="#placeBidModal">Place Your Bid</button>

                    @if(auth()->check() && auth()->user()->role === 'user')
                        <p class="text-muted">Your Current Funds: €{{ number_format(auth()->user()->funds, 2) }}</p>
                        <form action="{{ url('/properties/' . $id . '/fund') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="fundAmount" class="font-weight-bold">Enter Amount:</label>
                                <input type="number" id="fundAmount" name="fundAmount" class="form-control" required placeholder="Enter amount to contribute">
                            </div>
                            <button type="submit" class="btn btn-success mt-3">Contribute</button>
                        </form>
                        <!-- Place Your Bid Button -->
                    @else
                    @endif
                    @if(!auth()->check())
                        <p class="text-muted">Please log in to contribute to the fund.</p>
                    @endif
                </div>
            </div>

            <!-- Modal for placing a bid -->
            <div class="modal fade" id="placeBidModal" tabindex="-1" role="dialog" aria-labelledby="placeBidModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="placeBidModalLabel">Place Your Bid</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Your Available Funds: €{{ number_format(auth()->user()->funds, 2) }}</p>
                            <form action="{{ url('/properties/' . $id . '/bid') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="bidAmountModal" class="font-weight-bold">Enter Bid Amount:</label>
                                    <input type="number" id="bidAmountModal" name="bidAmount" class="form-control" required placeholder="Enter your bid amount">
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Bid</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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