@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-between">
        <div class="col">
            <h1>Manage Properties</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('admin.properties.create') }}" class="btn btn-success">Create New Property</a>
        </div>
    </div>
    <hr>
    <!-- Properties Table -->
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
                    <td>€{{ number_format($property->price) }}</td>
                    <td>{{ ucfirst($property->type) }}</td>
                    <td>
                        <a href="{{ url('/properties/' . $property->id) }}" class="btn btn-primary btn-sm">View</a>
                        <a href="{{ url('/admin/properties/' . $property->id . '/edit') }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ url('/admin/properties/' . $property->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
