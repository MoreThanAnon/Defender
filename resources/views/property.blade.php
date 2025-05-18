@extends('layouts.app')

@section('content')
<div class="container mt-5">
    @include('components.navbar')
        <div class="container mt-4">
        <h1 class="text-center fw-bold mb-4">{{ $name ?? 'Property Title' }}</h1>
    </div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-8">
<div id="propertyImagesCarousel" class="carousel slide mb-4" data-bs-ride="carousel" style="max-height: 450px; overflow: hidden;">
    <div class="carousel-inner" style="height: 100%;">
        @if(isset($images) && count($images) > 0)
            @foreach($images as $index => $image)
                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}" style="height: 100%;">
                    <img src="{{ asset('storage/' . $image->path) }}"
                         class="d-block w-100 h-100"
                         style="object-fit: contain;"
                         alt="Property Image">
                </div>
            @endforeach
        @else
            <div class="carousel-item active" style="height: 100%;">
                <img src="{{ asset('storage/default-placeholder.png') }}"
                     class="d-block w-100 h-100"
                     style="object-fit: contain;"
                     alt="No Images Available">
            </div>
        @endif
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#propertyImagesCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#propertyImagesCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>


        </div>

        <!-- Bid and Add Funds Section -->
        <div class="col-lg-4">
            <!-- Contribute to the Collective Fund -->
                        <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Your Current Funds</h5>
                    <p class="text-success">€{{ number_format(auth()->user()->balance, 2) }}</p>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Contribute to the Collective Fund</h5>
                    <p class="text-muted">Current Fund: €{{ number_format($collectiveFund, 2) }}</p>
                    <p class="text-muted">Goal: €{{ number_format($fundGoal, 2) }}</p>
                    <p class="text-muted">Percentage Raised: {{ round(($collectiveFund / $fundGoal) * 100, 2) }}%</p>
                </div>
            </div>
            @if ($property_status == 1) 
            <!-- Place Bid Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    @if($bids->isNotEmpty())
                        <h5 class="card-title">Update your Bid</h5>
                    @else
                        <h5 class="card-title">Place Your Bid</h5>
                    @endif
                    @if($userBid)
                        <p class="card-text text-success">
                            Your current bid <strong>€{{ number_format($userBid->amount, 2) }}</strong>.
                        </p>
                    @else
                        <p class="text-muted">You have not placed a bid on this property yet.</p>
                    @endif
                    @if(auth()->check())
                        <form id="bidForm" action="{{ url('/properties/' . $id . '/bid') }}" method="POST">
                            @csrf
                            <div class="form-group position-relative">
                                <label for="bidAmount" class="font-weight-bold">
                                    {{ $bids->isNotEmpty() ? 'Update Bid Amount:' : 'Enter Bid Amount:' }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">€</span>
                                    <input type="text" id="bidAmount" name="bidAmount" class="form-control" required placeholder="Enter your bid amount" value="{{ $userBid ? number_format($userBid->amount, 2) : '' }}">
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <button type="button" onClick="SubmitBid()" name="action" value="submit" class="btn btn-primary me-2">
                                    {{ $bids->isNotEmpty() ? 'Update Bid' : 'Enter Bid' }}
                                </button>
                                @if($userBid)
                                    <button type="button" name="action" value="remove" class="btn btn-danger" onClick="RemoveBid()">Remove Bid</button>
                                @endif
                            </div>
                        </form>
                        <div id="bidMessage" class="mt-3"></div>
                    @else
                    <p class="text-muted">
                        Please <a href="{{ route('login') }}">log in</a> to place a bid.
                    </p>
                    @endif
                </div>
            </div>
        @endif
        </div>
    </div>

    <!-- Tabs Navigation -->
<ul class="nav nav-tabs mt-4" id="propertyTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Details</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">Description</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab">Features</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="map-tab" data-bs-toggle="tab" data-bs-target="#map" type="button" role="tab">Map</button>
    </li>
</ul>

    <!-- Tabs Content -->
    <div class="tab-content mt-4" id="propertyTabsContent">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="details" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">
                        Property Details (Status: @if ($property_status == 1) Active @else Inactive @endif)
                    </h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="card-text">
                                <strong>Bed Rooms:</strong> {{ $beds }}<br>
                                <strong>Size:</strong> {{ $size }} m²<br>
                                <strong>Type:</strong> {{ ucfirst($type) }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="card-text">
                                <strong>BER Rating:</strong> {{ $ber_rating ?? 'N/A' }}<br>
                                <strong>Eircode:</strong> {{ $eircode ?? 'N/A' }}<br>
                                <strong>Daft.ie:</strong> 
                                @if($daft_link)
                                    <a href="{{ $daft_link }}" target="_blank">View Listing</a>
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Tab -->
        <div class="tab-pane fade" id="description" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Description</h5>
                    <hr>
                    <p class="card-text text-justify" style="line-height: 1.6;">
                        {{ trim(strip_tags($description ?? 'No description available.')) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Features Tab -->
        <div class="tab-pane fade" id="features" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Features</h5>
                    <hr>
                    <ul class="pl-3" style="list-style-type: disc;">
                        @if(isset($features) && is_array($features))
                            @foreach($features as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        @else
                            <li>No features listed.</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <!-- Map Tab -->
        <div class="tab-pane fade" id="map" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Location</h5>
                    <hr>
                    <div id="map" style="height: 400px;"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            //const map = L.map('map').setView([{{ $latitude }}, {{ $longitude }}], 13);
                           // L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            //    maxZoom: 19,
                           // }).addTo(map);
                           // L.marker([{{ $latitude }}, {{ $longitude }}]).addTo(map);
                        });

                        
                    </script>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->role === 'moderator'))
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('properties.edit', $id) }}" class="btn btn-warning me-2">
                Edit Property
            </a>
            <a href="#" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#setFundGoalModal">
                Set Fund Goal
            </a>
            @if ($property_status == 1)
                <a href="{{ route('properties.setStatus', ['id' => $id, 'status' => 0]) }}" class="btn btn-warning">
                    Set Property Inactive
                </a>
            @else
                <a href="{{ route('properties.setStatus', ['id' => $id, 'status' => 1]) }}" class="btn btn-warning">
                    Set Property Active
                </a>
            @endif
        </div>
    @endif
</div>

<!-- Set Fund Goal Modal -->
<div class="modal fade" id="setFundGoalModal" tabindex="-1" aria-labelledby="setFundGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="setFundGoalForm" action="{{ route('properties.setFundGoal', $id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="setFundGoalModalLabel">Set Fund Goal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="fundGoal">New Fund Goal (€)</label>
                        <input type="number" id="fundGoal" name="fundGoal" class="form-control" required placeholder="Enter new fund goal" min="0" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>

document.addEventListener('DOMContentLoaded', function () {
    const bidAmountInput = document.getElementById('bidAmount');

    if (bidAmountInput) {
        bidAmountInput.addEventListener('input', function (e) {
            // Remove any non-numeric characters except for the decimal point
            let value = e.target.value.replace(/[^0-9.]/g, '');

            // Split the value into whole and decimal parts
            const parts = value.split('.');
            const wholePart = parts[0];
            const decimalPart = parts[1] ? '.' + parts[1].slice(0, 2) : '';

            // Add commas to the whole part
            const formattedWholePart = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            // Combine the formatted whole part and decimal part
            e.target.value = formattedWholePart + decimalPart;
        });
    }
});

function RemoveBid() {
    const bidMessage = $('#bidMessage');

    $.ajax({
    url: "{{ route('properties.removeBid', ['id' => $id]) }}", // Laravel will generate /properties/1/bid/remove
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function (data) {
            if (data.success) {
                bidMessage.html('<div class="alert alert-success">Your bid was removed successfully.</div>');
                setTimeout(function () {
                    location.reload();
                }, 2000);
            } else {
                bidMessage.html('<div class="alert alert-danger">' + (data.message || 'Failed to remove bid.') + '</div>');
            }
        },
        error: function (xhr, status, error) {
            bidMessage.html('<div class="alert alert-danger">An error occurred while removing your bid.</div>');
        }
    });
}
function SubmitBid() {
    var bidForm = $('#bidForm');
    var bidAmountInput = $('#bidAmount');
    var bidMessage = $('#bidMessage');

    // Clear any previous messages
    bidMessage.html('');

    // Validate the bid amount
    var bidAmount = bidAmountInput.val().replace(/,/g, ''); // Remove commas
    if (!bidAmount || isNaN(bidAmount) || parseFloat(bidAmount) <= 0) {
        bidMessage.html('<div class="alert alert-danger">Please enter a valid bid amount.</div>');
        return;
    }

    // Append the property ID to the serialized form data
    bidAmountInput.val(bidAmount);
    var formData = bidForm.serialize() + '&id=' + encodeURIComponent('{{ $id }}');

    // Submit the form via AJAX
    $.ajax({
        url: bidForm.attr('action'),
        method: 'POST',
        data: formData, // Include the property ID
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            if (data.success) {
                bidMessage.html('<div class="alert alert-success">Your bid has been submitted successfully.</div>');
                setTimeout(function () {
                    location.reload();
                }, 2000); // Reload after 2 seconds
            } else {
                bidMessage.html('<div class="alert alert-danger">' + (data.message || 'An error occurred while submitting your bid.') + '</div>');
            }
        },
        error: function (xhr, status, error) {
            console.log(error);
            bidMessage.html('<div class="alert alert-danger">An error occurred while submitting your bid.</div>');
        }
    });
}
</script>
@endsection
