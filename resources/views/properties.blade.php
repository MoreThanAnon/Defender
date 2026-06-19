@extends('layouts.app')
@include('components.adminnav')

@section('content')
<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <button
                class="btn btn-secondary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#filterCollapse"
                aria-expanded="{{ $filtersActive ? 'true' : 'false' }}"
                aria-controls="filterCollapse"
            >
                {{ $filtersActive ? 'Hide Filters' : 'Show Filters' }}
            </button>

            <div class="collapse mt-3 {{ $filtersActive ? 'show' : '' }}" id="filterCollapse">
                <div class="card shadow-sm border-0 p-4">
                    <form method="GET" action="{{ route('properties.index') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="keyword" class="form-label">Keyword</label>
                                <input
                                    type="text"
                                    id="keyword"
                                    name="keyword"
                                    class="form-control"
                                    value="{{ request('keyword') }}"
                                    placeholder="Name, address, BER"
                                >
                            </div>

                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">Any Status</option>
                                    @foreach($propertyStatuses as $statusId => $statusName)
                                        <option value="{{ $statusId }}" @selected((string) request('status') === (string) $statusId)>
                                            {{ $statusName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="type" class="form-label">Type</label>
                                <select id="type" name="type" class="form-select">
                                    <option value="">Any Type</option>
                                    @foreach($propertyTypes as $type)
                                        <option value="{{ $type }}" @selected(request('type') === $type)>
                                            {{ ucfirst($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="eircodeRegion" class="form-label">Eircode Region</label>
                                <select id="eircodeRegion" name="eircodeRegion" class="form-select">
                                    <option value="">Any Region</option>
                                    @foreach($eircodeRegions as $region)
                                        <option value="{{ $region }}" @selected(request('eircodeRegion') === $region)>
                                            {{ $region }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="sort" class="form-label">Sort</label>
                                <select id="sort" name="sort" class="form-select">
                                    <option value="">Name</option>
                                    <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low to high</option>
                                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high to low</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="price_min" class="form-label">Min Price</label>
                                <input type="number" min="0" step="1000" id="price_min" name="price_min" class="form-control" value="{{ request('price_min') }}">
                            </div>

                            <div class="col-md-2">
                                <label for="price_max" class="form-label">Max Price</label>
                                <input type="number" min="0" step="1000" id="price_max" name="price_max" class="form-control" value="{{ request('price_max') }}">
                            </div>

                            <div class="col-md-2">
                                <label for="beds_min" class="form-label">Min Beds</label>
                                <input type="number" min="0" id="beds_min" name="beds_min" class="form-control" value="{{ request('beds_min') }}">
                            </div>

                            <div class="col-md-2">
                                <label for="baths_min" class="form-label">Min Baths</label>
                                <input type="number" min="0" id="baths_min" name="baths_min" class="form-control" value="{{ request('baths_min') }}">
                            </div>

                            <div class="col-md-2">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="activeBids"
                                        name="filter"
                                        value="activeBids"
                                        class="form-check-input"
                                        {{ request('filter') === 'activeBids' ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="activeBids">Only Active Bids</label>
                                </div>
                            </div>

                            <div class="col-md-2 d-flex gap-2 justify-content-md-end">
                                <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">Reset</a>
                                <button type="submit" class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($properties->isEmpty())
        <div class="alert alert-warning" role="alert">No properties found.</div>
    @endif

    @foreach($properties as $property)
        @php
            $statusLabel = $propertyStatuses[$property->status] ?? 'Unknown';
            $statusClass = match (strtolower($statusLabel)) {
                'active' => 'success',
                'funded' => 'primary',
                'inactive' => 'secondary',
                default => 'light text-dark',
            };
            $imagePath = $property->cover_image ?? 'default.jpg';
        @endphp

        <div class="card mb-3">
            <div class="row g-0">
                <div class="col-md-4">
                    <div class="overflow-hidden" style="height: 200px;">
                        <img
                            src="{{ asset('storage/' . $imagePath) }}"
                            alt="{{ $property->name }}"
                            class="img-fluid w-100 h-100 object-fit-cover rounded-start"
                        >
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title d-flex flex-wrap align-items-center gap-2">
                            <a href="{{ route('property.show', $property->id) }}">{{ $property->name }}</a>
                            <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                        </h5>
                        <p class="card-text">EUR {{ number_format($property->price, 2) }}</p>
                        <p class="card-text">
                            {{ $property->beds }} Bed &bull; {{ $property->baths }} Bath &bull; {{ number_format((float) $property->size, 2) }} m&sup2; &bull; {{ $property->type }}
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
</div>
@endsection
