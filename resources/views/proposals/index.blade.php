@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Proposals for {{ $property->name }}</h2>
    <a href="{{ route('proposals.create', $property->id) }}" class="btn btn-primary mb-3">New Proposal</a>
    <ul class="list-group">
        @foreach($proposals as $proposal)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="{{ route('proposals.show', $proposal->id) }}">{{ $proposal->title }}</a>
            </li>
        @endforeach
    </ul>
</div>
@endsection

