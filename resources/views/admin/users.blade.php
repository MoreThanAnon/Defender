@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Act as User</h1>
            <p class="text-muted mb-0">Enter a user ID or choose one from the list below.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Admin Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.impersonate') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label for="user_id" class="form-label">User ID</label>
                    <input
                        type="number"
                        min="1"
                        class="form-control @error('user_id') is-invalid @enderror"
                        id="user_id"
                        name="user_id"
                        value="{{ old('user_id') }}"
                        required
                    >
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-warning">Act as User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role }}</td>
                            <td class="text-end">EUR {{ number_format($user->balance, 2) }}</td>
                            <td class="text-end">
                                @if((int) $user->id === (int) session('impersonator_id', auth()->id()))
                                    <span class="text-muted">Current admin</span>
                                @else
                                    <form method="POST" action="{{ route('admin.impersonate') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Act as</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
