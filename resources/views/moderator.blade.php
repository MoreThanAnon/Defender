@extends('layouts.app')

@section('content')
    <div class="container py-5">
        @include('components.navbar')

        <h1>Welcome to the Moderator Page</h1>
        <p>This page is accessible only to admins and moderators.</p>
        <a href="{{ route('property.create') }}" class="btn btn-primary">Add new property</a>
    </div>
@endsection
