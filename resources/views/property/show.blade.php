@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                        </div>
                        <div class="col-md-9">
                            <h5 class="card-title">{{ $property->name }}</h5>
                            <p class="card-text">€{{ $property->price }}</p>
                            <p class="card-text">{{ $property->beds }} Bed • {{ $property->baths }} Bath • {{ $property->size }} m² • {{ $property->type }}</p>
                            <p class="card-text">BER: {{ $property->berRating }}</p>
                            <p class="card-text">{{ $property->address }}</p>
                            <p class="card-text">{{ $property->description }}</p>
                            <form action="{{ url('/properties/' . $property->id . '/bid') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="bidAmount">Place a Bid</label>
                                    <input type="number" id="bidAmount" name="bidAmount" class="form-control" required>
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
