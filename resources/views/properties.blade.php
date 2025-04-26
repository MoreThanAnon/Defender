@extends('layouts.app')

@section('content')
<div class="container mt-5">
    @include('components.navbar')

    <div class="row mb-4">
        <div class="col-md-12">
            <button class="btn btn-secondary" type="button" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                Show Filters
            </button>
            <div class="collapse mt-3" id="filterCollapse">
                <form class="form-row">
                    <!-- Filter Form Fields -->
                    <div class="form-group col-md-3">
                        <label for="radius">Radius</label>
                        <select id="radius" class="form-control">
                            <option>This location only</option>
                        </select>
                    </div>
                    <!-- Add other filter fields here -->
                    <div class="form-group col-md-12">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @foreach ($properties as $property)
    <div class="card mb-3">
        <div class="row no-gutters">
            <!-- Property Image -->
            <div class="col-md-4">
                @php
       
                    $imagePath = (isset($property->cover_image) ) ? $property->cover_image : '';
                @endphp
                <img src="{{ asset('storage/' . $imagePath) }}" class="card-img" alt="{{ $property->name }}">
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
