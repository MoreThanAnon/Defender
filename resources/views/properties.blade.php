@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <a class="navbar-brand" href="#">Defender</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/home') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/properties') }}">Properties</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/logout') }}">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="row mb-4">
        <div class="col-md-12">
            <button class="btn btn-secondary" type="button" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                Show Filters
            </button>
            <div class="collapse mt-3" id="filterCollapse">
                <form class="form-row">
                    <div class="form-group col-md-3">
                        <label for="radius">Radius</label>
                        <select id="radius" class="form-control">
                            <option>This location only</option>
                            <!-- Add more radius options here -->
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="priceMin">Price Min</label>
                        <input type="number" id="priceMin" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="priceMax">Price Max</label>
                        <input type="number" id="priceMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bedsMin">Beds Min</label>
                        <input type="number" id="bedsMin" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bedsMax">Beds Max</label>
                        <input type="number" id="bedsMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="type">Type</label>
                        <select id="type" class="form-control">
                            <option>Any Property</option>
                            <option>House</option>
                            <option>Detached House</option>
                            <option>Semi-Detached House</option>
                            <option>Terraced House</option>
                            <option>End of Terrace House</option>
                            <option>Townhouse</option>
                            <option>Apartment</option>
                            <option>Studio Apartment</option>
                            <option>Duplex</option>
                            <option>Bungalow</option>
                            <option>Site</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bathsMin">Baths Min</label>
                        <input type="number" id="bathsMin" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bathsMax">Baths Max</label>
                        <input type="number" id="bathsMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="facilities">Facilities</label>
                        <select id="facilities" class="form-control">
                            <option>Any Facilities</option>
                            <option>Alarm</option>
                            <option>Gas Fired Central Heating</option>
                            <option>Oil Fired Central Heating</option>
                            <option>Parking</option>
                            <option>Wheelchair Access</option>
                            <option>Wired for Cable TV</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="sizeMin">Size Min (m²)</label>
                        <input type="number" id="sizeMin" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="sizeMax">Size Max (m²)</label>
                        <input type="number" id="sizeMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="berRating">BER Rating</label>
                        <select id="berRating" class="form-control">
                            <option>Any</option>
                            <!-- Add more BER rating options here -->
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="saleType">Sale Type</label>
                        <select id="saleType" class="form-control">
                            <option>All Sales</option>
                            <option>Online Offers Only</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="mediaType">Media Type</label>
                        <select id="mediaType" class="form-control">
                            <option>Any Media</option>
                            <option>Ads with video</option>
                            <option>Ads with virtual tours</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="keyword">Keyword / Address Search</label>
                        <input type="text" id="keyword" class="form-control" placeholder='e.g. garage or "period property"'>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="availability">Availability</label>
                        <select id="availability" class="form-control">
                            <option>Available</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="addedInLast">Added In Last</label>
                        <select id="addedInLast" class="form-control">
                            <option>At any time</option>
                            <!-- Add more options here -->
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="sort">Sort</label>
                        <select id="sort" class="form-control">
                            <option>Best Match</option>
                            <!-- Add more sort options here -->
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @foreach ($properties as $property)
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                            </div>
                            <div class="col-md-9">
                                <h5 class="card-title">
                                    <a href="{{ url('/properties/' . $property->id) }}">{{ $property->name }}</a>
                                </h5>
                                <p class="card-text">€{{ $property->price }}</p>
                                <p class="card-text">{{ $property->beds }} Bed • {{ $property->baths }} Bath • {{ $property->size }} m² • {{ $property->type }}</p>
                                <p class="card-text">BER: {{ $property->berRating }}</p>
                                <p class="card-text">{{ $property->address }}</p>
                                <p class="card-text">{{ $property->description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
