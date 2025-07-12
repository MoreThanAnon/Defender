@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">{{ $proposal->title }}</h2>
    <p>{{ $proposal->description }}</p>
    <div class="mb-3">
        <strong>Yes:</strong> {{ $yes }}
        <strong class="ms-3">No:</strong> {{ $no }}
    </div>
    <form method="POST" action="{{ route('proposals.vote', $proposal->id) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label me-2">Cast your vote:</label>
            <select name="vote" class="form-select w-auto d-inline">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Submit Vote</button>
    </form>
</div>
@endsection

