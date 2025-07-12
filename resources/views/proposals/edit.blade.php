@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Edit Proposal</h2>
    <form method="POST" action="{{ route('proposals.update', $proposal->id) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ $proposal->title }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required>{{ $proposal->description }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection

