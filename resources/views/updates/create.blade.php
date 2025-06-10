@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="fw-bold text-center mb-4">Add Update to Property</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>There were some problems with your input:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('updates.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="property_id" value="{{ $property->id }}">

        <div class="mb-3">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Short Description</label>
            <input type="text" name="description" class="form-control" value="{{ old('description') }}">
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control" rows="6" required>{{ old('content') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="author" class="form-label">Author</label>
            <input type="text" name="author" class="form-control" value="{{ old('author', auth()->user()->name ?? '') }}">
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Upload Image</label>
            <input type="file" name="image" class="form-control">
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="{{ route('properties.show', $property->id) }}" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit Update</button>
        </div>
    </form>
</div>
@endsection
