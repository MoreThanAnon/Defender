@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-between">
        <div class="col">
            <h1>Manage Properties</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('property.create') }}" class="btn btn-success">Create New Property</a>
        </div>
    </div>
    <hr>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($properties as $property)
                <tr>
                    <td>{{ $property->id }}</td>
                    <td>{{ $property->name }}</td>
                    <td>EUR {{ number_format($property->price, 2) }}</td>
                    <td>{{ ucfirst($property->type) }}</td>
                    <td>
                        <a href="{{ url('/properties/' . $property->id) }}" class="btn btn-primary btn-sm">View</a>
                        <a href="{{ route('properties.edit', $property->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
