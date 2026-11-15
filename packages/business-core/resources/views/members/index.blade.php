@extends(config('business-core.layout'))
@section('title', 'People')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <h1 class="h4">People</h1>
        <div class="card mb-4"><table class="table mb-0 align-middle">
            <thead><tr><th>Person</th><th>Role</th>@bizcan('members')<th></th>@endbizcan</tr></thead>
            <tbody>
            @foreach ($memberships as $membership)
                <tr>
                    <td>{{ \BusinessCore\Support\CurrentBusiness::displayName($membership->user) }}<div class="small text-muted">{{ $membership->user?->email }}</div></td>
                    <td>
                        @bizcan('members')
                            <form method="POST" action="{{ route('bc.members.update', $membership) }}" class="d-flex gap-1">
                                @csrf @method('PUT')
                                <select class="form-select form-select-sm" name="role" aria-label="Role">
                                    @foreach (config('business-core.roles') as $value => $role)<option value="{{ $value }}" @selected($membership->role === $value)>{{ $role['label'] }}</option>@endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        @else
                            {{ $membership->roleLabel() }}
                        @endbizcan
                    </td>
                    @bizcan('members')
                        <td class="text-end">
                            <form method="POST" action="{{ route('bc.members.destroy', $membership) }}" onsubmit="return confirm('Remove this person from the business?')">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Remove</button>
                            </form>
                        </td>
                    @endbizcan
                </tr>
            @endforeach
            </tbody>
        </table></div>

        @if ($invitations->isNotEmpty())
            <h2 class="h6">Invitations not yet accepted</h2>
            <div class="card"><table class="table table-sm mb-0 align-middle">
                @foreach ($invitations as $invitation)
                    <tr>
                        <td>{{ $invitation->email }}<div class="small text-muted">{{ config('business-core.roles.'.$invitation->role.'.label') }} · expires {{ $invitation->expires_at?->format('j M Y') }}</div></td>
                        <td><input class="form-control form-control-sm" readonly value="{{ route('bc.invitations.show', ['token' => $invitation->token]) }}" onclick="this.select()" aria-label="Invitation link"></td>
                        <td><form method="POST" action="{{ route('bc.members.revoke', $invitation) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Withdraw</button></form></td>
                    </tr>
                @endforeach
            </table></div>
        @endif
    </div>

    <div class="col-lg-4">
        @bizcan('members')
            <form method="POST" action="{{ route('bc.members.invite') }}" class="card mb-3"><div class="card-body">
                @csrf
                <h2 class="h6">Invite someone</h2>
                <div class="mb-2"><label class="form-label small mb-0" for="email">Their email</label>
                    <input class="form-control form-control-sm" id="email" name="email" type="email" required></div>
                <div class="mb-3"><label class="form-label small mb-0" for="role">Role</label>
                    <select class="form-select form-select-sm" id="role" name="role">
                        @foreach (config('business-core.roles') as $value => $role)<option value="{{ $value }}" @selected($value === 'member')>{{ $role['label'] }}</option>@endforeach
                    </select></div>
                <button class="btn btn-primary btn-sm">Create invitation link</button>
                <div class="form-text">You get a link to send them. It only works for an account with that email address.</div>
            </div></form>
        @endbizcan
        <div class="card"><div class="card-body small">
            <h2 class="h6">What each role can do</h2>
            <p class="mb-1"><strong>Director</strong> — everything: people, settings, approving proposals, signing off reports, export.</p>
            <p class="mb-1"><strong>Finance lead</strong> — import statements, categorise, prepare and reconcile payments, reports.</p>
            <p class="mb-1"><strong>Site / operator</strong> — projects, assets, suppliers, tasks, proposals.</p>
            <p class="mb-1"><strong>Member</strong> — view, discuss and vote.</p>
            <p class="mb-2"><strong>Accountant / auditor</strong> — read-only, including finance and audit history.</p>
            <p class="text-muted mb-0">A role here is not legal authority. It never gives anyone access to the bank account.</p>
        </div></div>
    </div>
</div>
@endsection
