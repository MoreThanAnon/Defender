@extends('layouts.app')

@section('content')
<div class="container mt-5">

    <div class="row mb-4">
        <div class="col-md-12">
        <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
            Show Filters
        </button>
            
<div class="collapse mt-3" id="filterCollapse">
    <div class="card shadow-sm border-0 p-4">
        <form>
            <div class="row g-3">

                <!-- Radius + Eircode + Active Bids -->
                <div class="col-md-3">
                    <label for="radius" class="form-label">Radius</label>
                    <select id="radius" class="form-select">
                        <option>This location only</option>
                    </select>
                </div>
            <div class="col-md-4">
                <label for="eircodeRegion" class="form-label">Eircode Region</label>
                <select id="eircodeRegion" name="eircodeRegion" class="form-select">
                    <option value="">Any Region</option>
                    <option value="A92">Louth</option>
                    <option value="Y14">Wicklow</option>
                    <option value="A84">Meath</option>
                    <option value="H65">Galway</option>
                    <option value="N37">Westmeath</option>
                    <option value="R14">Kildare</option>
                    <option value="K32">Dublin</option>
                    <option value="F26">Mayo</option>
                    <option value="H53">Galway</option>
                    <option value="P31">Cork</option>
                    <option value="F31">Mayo</option>
                    <option value="A75">Monaghan</option>
                    <option value="A41">Dublin</option>
                    <option value="F35">Mayo</option>
                    <option value="F56">Sligo</option>
                    <option value="P72">Cork</option>
                    <option value="P75">Cork</option>
                    <option value="H14">Cavan</option>
                    <option value="R42">Offaly</option>
                    <option value="A94">Dublin</option>
                    <option value="F52">Roscommon</option>
                    <option value="A98">Wicklow</option>
                    <option value="V23">Kerry</option>
                    <option value="E21">Tipperary</option>
                    <option value="R93">Carlow</option>
                    <option value="A81">Monaghan</option>
                    <option value="N41">Leitrim</option>
                    <option value="E32">Tipperary</option>
                    <option value="P43">Cork</option>
                    <option value="E25">Tipperary</option>
                    <option value="F23">Mayo</option>
                    <option value="F45">Roscommon</option>
                    <option value="H12">Cavan</option>
                    <option value="P56">Cork</option>
                    <option value="F12">Mayo</option>
                    <option value="H71">Galway</option>
                    <option value="P85">Cork</option>
                    <option value="H23">Monaghan</option>
                    <option value="E91">Tipperary</option>
                    <option value="P24">Cork</option>
                    <option value="H16">Cavan</option>
                    <option value="T12">Cork</option>
                    <option value="T23">Cork</option>
                    <option value="R56">Kildare</option>
                    <option value="F94">Donegal</option>
                    <option value="D01">Dublin</option>
                    <option value="H91">Galway</option>
                    <option value="Y35">Wexford</option>
                    <option value="R95">Kilkenny</option>
                    <option value="V92">Kerry</option>
                    <option value="X91">Waterford</option>
                    <option value="N91">Westmeath</option>
                </select>
            </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" id="activeBids" name="filter" value="activeBids"
                            class="form-check-input" {{ request('filter') == 'activeBids' ? 'checked' : '' }}>
                        <label class="form-check-label" for="activeBids">Only Active Bids</label>
                    </div>
                </div>

                {{-- Commented Out: Price Filters --}}
                {{--
                <div class="col-md-3">
                    <label for="priceMin" class="form-label">Price Min</label>
                    <input type="number" id="priceMin" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="priceMax" class="form-label">Price Max</label>
                    <input type="number" id="priceMax" class="form-control">
                </div>
                --}}

                {{-- Commented Out: Beds & Baths --}}
                {{--
                <div class="col-md-3">
                    <label for="bedsMin" class="form-label">Beds Min</label>
                    <input type="number" id="bedsMin" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="bedsMax" class="form-label">Beds Max</label>
                    <input type="number" id="bedsMax" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="bathsMin" class="form-label">Baths Min</label>
                    <input type="number" id="bathsMin" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="bathsMax" class="form-label">Baths Max</label>
                    <input type="number" id="bathsMax" class="form-control">
                </div>
                --}}

                {{-- Commented Out: Property Type & Facilities --}}
                {{--
                <div class="col-md-3">
                    <label for="type" class="form-label">Property Type</label>
                    <select id="type" class="form-select">
                        <option>Any Property</option>
                        <!-- more -->
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="facilities" class="form-label">Facilities</label>
                    <select id="facilities" class="form-select">
                        <option>Any Facilities</option>
                        <!-- more -->
                    </select>
                </div>
                --}}

                {{-- Commented Out: Size & BER --}}
                {{--
                <div class="col-md-3">
                    <label for="sizeMin" class="form-label">Size Min (m²)</label>
                    <input type="number" id="sizeMin" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="sizeMax" class="form-label">Size Max (m²)</label>
                    <input type="number" id="sizeMax" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="berRating" class="form-label">BER Rating</label>
                    <select id="berRating" class="form-select">
                        <option>Any</option>
                    </select>
                </div>
                --}}

                {{-- Commented Out: Keyword --}}
                {{--
                <div class="col-md-6">
                    <label for="keyword" class="form-label">Keyword / Address</label>
                    <input type="text" id="keyword" class="form-control" placeholder="e.g. garage, city center, period property">
                </div>
                --}}

                {{-- Commented Out: Sort --}}
                {{--
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort</label>
                    <select id="sort" class="form-select">
                        <option>Best Match</option>
                    </select>
                </div>
                --}}

                <!-- Submit Button -->
                <div class="col-12 text-end mt-2">
                    <button type="submit" class="btn btn-primary px-4">Search</button>
                </div>
            </div>
        </form>
    </div>
</div>



        </div>
    </div>
    @php
        if (!isset($properties) || count($properties) == 0) {
            echo '<div class="alert alert-warning" role="alert">No properties found.</div>';
        }
    @endphp
    @foreach ($properties as $property)
    <div class="card mb-3">
        <div class="row no-gutters">
            <!-- Property Image -->
            <div class="col-md-4">
                @php
                    $imagePath = $property->cover_image ?? 'default.jpg';
                @endphp
                <div class="overflow-hidden" style="height: 200px;">
                    <img src="{{ asset('storage/' . $imagePath) }}" 
                        alt="{{ $property->name }}"
                        class="img-fluid w-100 h-100 object-fit-cover rounded-start">
                </div>
            </div>

            <!-- Property Details -->
            <div class="col-md-8">
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="{{ url('/properties/' . $property->id) }}">{{ $property->name }}</a>
                    </h5>
                    <p class="card-text">€{{ number_format($property->price, 2) }}</p>
                    <p class="card-text">
                        {{ $property->beds }} Bed • {{ $property->baths }} Bath • {{ $property->size }} m² • {{ $property->type }}
                    </p>
                    <p class="card-text">BER: {{ $property->ber_rating }}</p>
                    <p class="card-text">{{ $property->address }}</p>
                    <p class="card-text text-truncate" style="max-width: 100%;">
                        {{ $property->description }}
                    </p>
                </div>
            </div>
        </div>
    </div>

@endforeach
@endsection
