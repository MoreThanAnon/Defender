@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">New Proposal for {{ $property->name }}</h2>
    <form method="POST" action="{{ route('proposals.store', $property->id) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Create</button>
    </form>
</div>
@endsection

