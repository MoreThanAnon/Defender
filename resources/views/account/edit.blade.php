@extends('layouts.app')
@section('title', 'My account')

@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
    <h1 class="h3 mb-3">My account</h1>
    <form method="POST" action="{{ route('account.update') }}" class="card"><div class="card-body">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label">Username</label>
            <input class="form-control" value="{{ $user->username }}" disabled></div>
        <div class="mb-3"><label class="form-label" for="real_name">Full name</label>
            <input class="form-control" id="real_name" name="real_name" value="{{ old('real_name', $user->real_name) }}"></div>
        <div class="mb-3"><label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
            <div class="form-text">Invitations to a business are matched on this address.</div></div>
        <hr>
        <p class="small text-muted">Leave these blank to keep your current password.</p>
        <div class="mb-3"><label class="form-label" for="current_password">Current password</label>
            <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password"></div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label" for="password">New password</label>
                <input class="form-control" id="password" name="password" type="password" autocomplete="new-password"></div>
            <div class="col-md-6"><label class="form-label" for="password_confirmation">Repeat new password</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
        </div>
        <button class="btn btn-primary">Save</button>
    </div></form>
</div></div>
@endsection
