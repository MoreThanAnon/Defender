@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $property->name }}</h1>
            <p>€{{ $property->price }}</p>
            <p>{{ $property->beds }} Bed • {{ $property->baths }} Bath • {{ $property->size }} m² • {{ $property->type }}</p>
            <p>BER: {{ $property->berRating }}</p>
            <p>{{ $property->address }}</p>
            <p>{{ $property->description }}</p>
            <div class="row">
                @foreach ($property->images as $image)
                    <div class="col-md-4">
                        <img src="{{ $image }}" class="img-fluid" alt="Property Image">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
