<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\Document;
use BusinessCore\Models\Project;
use BusinessCore\Models\Proposal;
use BusinessCore\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function index()
    {
        $this->allow('view');

        return $this->view('proposals.index', [
            'proposals' => Proposal::with(['project', 'author'])->withCount('votes')->orderByRaw("status = 'open' desc")->latest()->get(),
            'projects' => Project::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->allow('propose');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'body' => ['nullable', 'string', 'max:10000'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'project_id' => ['nullable', $this->owned('business_projects')],
            'supplier_id' => ['nullable', $this->owned('business_suppliers')],
            'closes_at' => ['nullable', 'date', 'after:now'],
        ]);

        $proposal = Proposal::create($data + ['created_by' => $request->user()->getKey()]);
        $this->audit('proposal.created', $proposal, ['title' => $proposal->title, 'amount' => $proposal->amount]);

        return redirect()->route('bc.proposals.show', $proposal)->with('status', 'Proposal opened for discussion.');
    }

    public function show(Request $request, Proposal $proposal)
    {
        $this->allow('view');

        return $this->view('proposals.show', [
            'proposal' => $proposal->load(['project', 'supplier', 'author', 'decider', 'comments.user', 'votes.user', 'paymentTasks']),
            'tally' => $proposal->tally(),
            'myVote' => $proposal->votes->firstWhere('user_id', $request->user()->getKey()),
            'documents' => Document::where('attached_type', Proposal::class)->where('attached_id', $proposal->id)->latest()->get(),
        ]);
    }

    public function vote(Request $request, Proposal $proposal)
    {
        $this->allow('vote');
        abort_unless($proposal->votingOpen(), 422, 'Voting on this proposal has closed.');

        $data = $request->validate(['choice' => ['required', Rule::in(['yes', 'no', 'abstain'])]]);

        $proposal->votes()->updateOrCreate(['user_id' => $request->user()->getKey()], $data);
        $this->audit('proposal.vote_cast', $proposal, $data);

        return back()->with('status', 'Your vote is recorded.');
    }

    public function comment(Request $request, Proposal $proposal)
    {
        $this->allow('view');
        abort_if($this->current()->role() === 'auditor', 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $proposal->comments()->create($data + ['user_id' => $request->user()->getKey()]);

        return back()->with('status', 'Comment added.');
    }

    /** An authorised person records the actual decision. The vote informs it; it does not replace it. */
    public function decide(Request $request, Proposal $proposal)
    {
        $this->allow('decide');
        abort_unless($proposal->isOpen(), 422, 'This proposal has already been decided.');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $tally = $proposal->tally();
        $proposal->update([
            'status' => $data['decision'],
            'decision_note' => $data['decision_note'] ?? null,
            'decided_by' => $request->user()->getKey(),
            'decided_at' => now(),
        ]);
        $this->audit('proposal.'.$data['decision'], $proposal, ['amount' => $proposal->amount, 'tally' => $tally]);

        return back()->with('status', 'Decision recorded: '.$data['decision'].'.');
    }
}
