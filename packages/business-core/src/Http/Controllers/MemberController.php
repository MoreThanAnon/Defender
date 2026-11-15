<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Contracts\AuditRecorder;
use BusinessCore\Models\Invitation;
use BusinessCore\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index()
    {
        $this->allow('view');

        return $this->view('members.index', [
            'memberships' => Membership::with('user')->get()->sortBy('role'),
            'invitations' => $this->current()->can('members') ? Invitation::whereNull('accepted_at')->latest()->get() : collect(),
        ]);
    }

    public function invite(Request $request)
    {
        $this->allow('members');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'role' => ['required', Rule::in(array_keys(config('business-core.roles')))],
        ]);

        $invitation = Invitation::create($data + [
            'token' => Str::random(48),
            'invited_by' => $request->user()->getKey(),
            'expires_at' => now()->addDays((int) config('business-core.invitation_days', 14)),
        ]);
        $this->audit('member.invited', $invitation, ['email' => $data['email'], 'role' => $data['role']]);

        return back()->with('status', 'Invitation created. Send them this link: '.route('bc.invitations.show', ['token' => $invitation->token]));
    }

    public function update(Request $request, Membership $membership)
    {
        $this->allow('members');

        $data = $request->validate(['role' => ['required', Rule::in(array_keys(config('business-core.roles')))]]);

        if ($membership->role === 'director' && $data['role'] !== 'director' && $this->isLastDirector()) {
            return back()->withErrors(['role' => 'A business must keep at least one director.']);
        }

        $from = $membership->role;
        $membership->update($data);
        $this->audit('member.role_changed', $membership, ['user_id' => $membership->user_id, 'from' => $from, 'to' => $data['role']]);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(Membership $membership)
    {
        $this->allow('members');

        if ($membership->role === 'director' && $this->isLastDirector()) {
            return back()->withErrors(['role' => 'A business must keep at least one director.']);
        }

        $this->audit('member.removed', $membership, ['user_id' => $membership->user_id, 'role' => $membership->role]);
        $membership->delete();

        return back()->with('status', 'Person removed.');
    }

    public function revoke(Invitation $invitation)
    {
        $this->allow('members');
        $invitation->delete();

        return back()->with('status', 'Invitation withdrawn.');
    }

    public function showInvitation(string $token)
    {
        $invitation = $this->findInvitation($token);

        return $this->view('members.invitation', ['invitation' => $invitation, 'target' => $invitation->business]);
    }

    public function acceptInvitation(Request $request, string $token, AuditRecorder $audit)
    {
        $invitation = $this->findInvitation($token);
        $user = $request->user();

        // The link alone is not enough: it only works for the address it was sent to.
        abort_unless(strcasecmp($user->email, $invitation->email) === 0, 403, 'This invitation was sent to a different email address.');

        Membership::withoutGlobalScope('business')->firstOrCreate(
            ['business_id' => $invitation->business_id, 'user_id' => $user->getKey()],
            ['role' => $invitation->role],
        );
        $invitation->update(['accepted_at' => now()]);
        $audit->record('member.joined', $invitation, ['role' => $invitation->role], $invitation->business);

        return redirect()->route('bc.show', $invitation->business)->with('status', 'Welcome. You now have access.');
    }

    private function findInvitation(string $token): Invitation
    {
        $invitation = Invitation::withoutGlobalScope('business')->where('token', $token)->with('business')->first();
        abort_if(! $invitation || ! $invitation->isOpen(), 404);

        return $invitation;
    }

    private function isLastDirector(): bool
    {
        return Membership::where('role', 'director')->count() <= 1;
    }
}
