@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="container mt-4 d-flex justify-content-between align-items-center">
        <h1 class="fw-bold mb-4">{{ $name ?? 'Property Title' }}</h1>
        @auth
            @if(auth()->user()->hasRole('moderator') || auth()->user()->hasRole('admin'))
                <a href="{{ route('updates.create', $id) }}" class="btn btn-sm btn-primary">Add Update</a>
            @endif
        @endauth
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- Image Carousel -->
            <div id="propertyImagesCarousel" class="carousel slide mb-4" data-bs-ride="carousel" style="max-height: 450px; overflow: hidden;">
                <div class="carousel-inner" style="height: 100%;">
                    @if(isset($images) && count($images) > 0)
                        @foreach($images as $index => $image)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <img src="{{ asset('storage/' . $image->path) }}" class="d-block w-100 h-100" style="object-fit: contain;" alt="Property Image">
                            </div>
                        @endforeach
                    @else
                        <div class="carousel-item active">
                            <img src="{{ asset('storage/default-placeholder.png') }}" class="d-block w-100 h-100" style="object-fit: contain;" alt="No Images Available">
                        </div>
                    @endif
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#propertyImagesCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#propertyImagesCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Countdown Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title mb-4">⏳ Time Remaining</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                        <span class="fw-semibold">
                            🕒 Total Bidding
                            <i class="bi bi-question-circle-fill text-muted ms-1"
                               data-bs-toggle="tooltip"
                               data-bs-trigger="click"
                               data-bs-placement="top"
                               title="This is the final deadline for all users to submit bids on the property."
                               tabindex="0">?</i>
                        </span>
                        <span id="totalCountdown" class="badge bg-primary fs-6 px-3 py-2"></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center px-2">
                        <span class="fw-semibold">
                            🧑‍💼 Local Bidding
                            <i class="bi bi-question-circle-fill text-muted ms-1"
                               data-bs-toggle="tooltip"
                               data-bs-trigger="click"
                               data-bs-placement="top"
                               title="Only local users (same Eircode region) can place bids during this early period."
                               tabindex="0">?</i>
                        </span>
                        <span id="internalCountdown" class="badge bg-info fs-6 px-3 py-2"></span>
                    </div>
                </div>
            </div>

            <!-- Funding Progress -->
            <div class="card shadow-sm mb-4" id="fundingProgressSection">
                <div class="card-body text-center">
                    <h5 class="card-title">Funding Progress</h5>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar"
                             id="fundingProgressBar"
                             style="width: {{ round(($collectiveFund / $fundGoal) * 100, 2) }}%;"
                             aria-valuenow="{{ round(($collectiveFund / $fundGoal) * 100, 2) }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            {{ round(($collectiveFund / $fundGoal) * 100, 2) }}%
                        </div>
                    </div>
                    <p id="fundingProgressText">€{{ number_format($collectiveFund, 2) }} raised of €{{ number_format($fundGoal, 2) }}</p>
                </div>
            </div>

            <!-- Bid Section -->
            @if ($property_status == 1)
                @if ($localsOnly)
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <h5 class="card-title text-warning">Locals-Only Bidding</h5>
                            <p class="text-muted">
                                Only users from <strong>{{ $propertyRegion }}</strong> can bid until
                                <strong>{{ \Carbon\Carbon::parse($campaignStart)->addDays($internalDays)->format('F j, Y \\a\\t H:i') }}</strong>.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <h5 class="card-title">{{ $bids->isNotEmpty() ? 'Update your Bid' : 'Place Your Bid' }}</h5>

                            @if($userBid)
                                <p class="text-success">
                                    Your current bid: <strong>€{{ number_format($userBid->amount, 2) }}</strong>
                                </p>
                            @else
                                <p class="text-muted">You haven't placed a bid yet.</p>
                            @endif

                            @auth
                                <form id="bidForm" action="{{ url('/properties/' . $id . '/bid') }}" method="POST">
                                    @csrf
                                    <div class="form-group position-relative">
                                        <label for="bidAmount" class="fw-bold">
                                            {{ $bids->isNotEmpty() ? 'Update Bid Amount:' : 'Enter Bid Amount:' }}
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">€</span>
                                            <input type="text" id="bidAmount" name="bidAmount" class="form-control"
                                                   required placeholder="Enter your bid"
                                                   value="{{ $userBid ? number_format($userBid->amount, 2) : '' }}">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center mt-3">
                                        <button type="button" onClick="SubmitBid()" class="btn btn-primary me-2">
                                            {{ $bids->isNotEmpty() ? 'Update Bid' : 'Enter Bid' }}
                                        </button>
                                        @if($userBid)
                                            <button type="button" class="btn btn-danger" onClick="RemoveBid()">Remove Bid</button>
                                        @endif
                                    </div>
                                </form>
                                <div id="bidMessage" class="mt-3"></div>
                            @else
                                <p class="text-muted">Please <a href="{{ route('login') }}">log in</a> to bid.</p>
                            @endauth
                        </div>
                    </div>
                @endif
            @endif

            <!-- Place Your Bid Section -->
            <div class="card shadow-sm mb-4" id="placeBidSection">
                <div class="card-body text-center">
                    <!-- ...existing bid form and info... -->
                </div>
            </div>

            <!-- Funds Overview -->
            <div class="card shadow-sm mb-4" id="fundsOverviewSection">
                <div class="card-body text-center">
                    <h5 class="card-title">Your Current Funds</h5>
                    <p class="text-success" id="userFunds">€{{ number_format(auth()->user()->balance, 2) }}</p>
                </div>
            </div>
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
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="updates-tab" data-bs-toggle="tab" data-bs-target="#updates" type="button" role="tab">Updates</button>
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
                        <p><strong>Bed Rooms:</strong> {{ $beds }}</p>
                        <p><strong>Size:</strong> {{ $size }} m²</p>
                        <p><strong>Type:</strong> {{ ucfirst($type) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>BER Rating:</strong> {{ $ber_rating ?? 'N/A' }}</p>
                        <p><strong>Eircode:</strong> {{ $eircode ?? 'N/A' }}</p>
                        <p><strong>Daft.ie:</strong>
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
                <p class="text-justify" style="line-height: 1.6;">
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
@auth
    @if(auth()->user()->hasRole('moderator') || auth()->user()->hasRole('admin'))
        <a href="{{ route('updates.create', $id) }}" class="btn btn-sm btn-primary">Add Update</a>
    @endif
@endauth
@auth
    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('moderator'))
        <a href="{{ route('properties.edit', $id) }}" class="btn btn-warning">Edit Property</a>
    @endif
@endauth
    <!-- Map Tab -->
    <div class="tab-pane fade" id="map" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Location</h5>
                <hr>
                <div id="map" style="height: 400px;"></div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // Uncomment and use actual lat/long to enable map
                        // const map = L.map('map').setView([{{ $latitude }}, {{ $longitude }}], 13);
                        // L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        //     maxZoom: 19,
                        // }).addTo(map);
                        // L.marker([{{ $latitude }}, {{ $longitude }}]).addTo(map);
                    });
                </script>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="updates" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Property Updates</h5>
                <hr>
                @forelse($updates as $update)
                    <div class="mb-4">
                        <h6 class="fw-bold">{{ $update->title }}</h6>
                        <p class="small text-muted">{{ $update->created_at->format('M d, Y') }}@if($update->author) by {{ $update->author }}@endif</p>
                        @if($update->image)
                            <img src="{{ asset('storage/' . $update->image) }}" class="img-fluid mb-2" alt="update image">
                        @endif
                        <p>{{ $update->content }}</p>
                        @if($update->file)
                            <a href="{{ asset('storage/' . $update->file) }}" target="_blank" class="btn btn-sm btn-outline-primary">Download {{ strtoupper($update->file_type) }}</a>
                        @endif
                    </div>
                @empty
                    <p>No updates available.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

</div>

<!-- Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const totalEnd = new Date("{{ \Carbon\Carbon::parse($campaignEnd)->format('Y-m-d H:i:s') }}").getTime();
        const internalEnd = new Date("{{ \Carbon\Carbon::parse($campaignStart)->addDays($internalDays)->format('Y-m-d H:i:s') }}").getTime();

        function updateCountdowns() {
            const now = new Date().getTime();
            const totalRemaining = totalEnd - now;
            const internalRemaining = internalEnd - now;

            function format(ms) {
                if (ms <= 0) return "Expired";
                const d = Math.floor(ms / (1000 * 60 * 60 * 24));
                const h = Math.floor((ms % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((ms % (1000 * 60)) / 1000);
                return `${d}d ${h}h ${m}m ${s}s`;
            }

            document.getElementById("totalCountdown").textContent = format(totalRemaining);
            document.getElementById("internalCountdown").textContent = format(internalRemaining);
        }

        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    });

    document.addEventListener('DOMContentLoaded', function () {
        const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltips.map(t => new bootstrap.Tooltip(t));
    });

    function SubmitBid() {
        const form = document.getElementById('bidForm');
        const bidAmount = document.getElementById('bidAmount').value;
        const bidMessage = document.getElementById('bidMessage');
        bidMessage.innerHTML = '';

        if (!bidAmount || isNaN(bidAmount) || parseFloat(bidAmount) <= 0) {
            bidMessage.innerHTML = '<div class="alert alert-danger">Please enter a valid bid amount.</div>';
            return;
        }

        // Submit the form via AJAX
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                bidAmount: bidAmount
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bidMessage.innerHTML = '<div class="alert alert-success">Your bid has been submitted successfully.</div>';

                // Update Funding Progress
                const progressBar = document.getElementById('fundingProgressBar');
                const progressText = document.getElementById('fundingProgressText');
                const userFunds = document.getElementById('userFunds');

                const percent = Math.round((data.collectiveFund / data.fundGoal) * 10000) / 100;
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent);
                progressText.textContent =
                    '€' + Number(data.collectiveFund).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                    ' raised of €' + Number(data.fundGoal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                userFunds.textContent =
                    '€' + Number(data.userbalance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

                // Highlight updates
                highlightUpdate(progressBar);
                highlightUpdate(progressText);
                highlightUpdate(userFunds);

                // Optionally, update the bid amount in the form
                document.getElementById('bidAmount').value = parseFloat(bidAmount).toFixed(2);
            } else {
                bidMessage.innerHTML = '<div class="alert alert-danger">' + (data.message || 'An error occurred.') + '</div>';
            }
        })
        .catch(() => {
            bidMessage.innerHTML = '<div class="alert alert-danger">An error occurred while submitting your bid.</div>';
        });
    }

    function RemoveBid() {
        if (!confirm('Are you sure you want to remove your bid?')) return;
        const form = document.getElementById('bidForm');
        const bidMessage = document.getElementById('bidMessage');
        bidMessage.innerHTML = '';

        // Build the remove URL (replace /bid with /bid/remove)
        let removeUrl = form.action.replace(/\/bid$/, '/bid/remove');

        fetch(removeUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bidMessage.innerHTML = '<div class="alert alert-success">Your bid has been removed.</div>';
                setTimeout(() => location.reload(), 1500);
            } else {
                bidMessage.innerHTML = '<div class="alert alert-danger">' + (data.message || 'An error occurred.') + '</div>';
            }
        })
        .catch(() => {
            bidMessage.innerHTML = '<div class="alert alert-danger">An error occurred while removing your bid.</div>';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const bidForm = document.getElementById('bidForm');
        if (bidForm) {
            bidForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent normal form submission
                SubmitBid();        // Call your AJAX function
            });
        }
    });

    function highlightUpdate(element) {
        if (!element) return;
        element.style.transition = 'background-color 0.5s';
        element.style.backgroundColor = '#ffe066'; // light yellow highlight
        setTimeout(() => {
            element.style.backgroundColor = '';
        }, 800);
    }
</script>
@endsection
