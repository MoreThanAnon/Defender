@extends(config('business-core.layout'))
@section('title', 'Invitation')

@section('content')
<div class="row justify-content-center"><div class="col-lg-6"><div class="card"><div class="card-body">
    <h1 class="h4">Join {{ $target->name }}</h1>
    <p>You have been invited as <strong>{{ config('business-core.roles.'.$invitation->role.'.label', $invitation->role) }}</strong>.</p>
    @if (strcasecmp(auth()->user()->email, $invitation->email) === 0)
        <form method="POST" action="{{ route('bc.invitations.accept', ['token' => $invitation->token]) }}">
            @csrf
            <button class="btn btn-primary">Accept invitation</button>
        </form>
    @else
        <div class="alert alert-warning mb-0">This invitation was sent to a different email address than the one you are signed in with ({{ auth()->user()->email }}). Sign in with the invited address to accept it.</div>
    @endif
</div></div></div></div>
@endsection
