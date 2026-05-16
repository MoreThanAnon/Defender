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
                <th>Status</th>
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
                    <td>{{ $propertyStatuses[$property->status] ?? 'Unknown' }}</td>
                    <td>
                        <a href="{{ url('/properties/' . $property->id) }}" class="btn btn-primary btn-sm">View</a>
                        <a href="{{ route('properties.edit', $property->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        @if((int) $property->status === 1)
                            <form action="{{ route('properties.setStatus', ['id' => $property->id, 'status' => 2]) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Deactivate</button>
                            </form>
                        @else
                            <form action="{{ route('properties.setStatus', ['id' => $property->id, 'status' => 1]) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm">Activate</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
