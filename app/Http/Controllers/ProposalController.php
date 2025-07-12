<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Proposal;
use App\Models\Vote;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function index(Property $property)
    {
        $proposals = $property->proposals;
        return view('proposals.index', compact('property', 'proposals'));
    }

    public function create(Property $property)
    {
        return view('proposals.create', compact('property'));
    }

    public function store(Request $request, Property $property)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $property->proposals()->create($data);

        return redirect()->route('proposals.index', $property);
    }

    public function show(Proposal $proposal)
    {
        $yes = $proposal->votes()->where('vote', true)->count();
        $no = $proposal->votes()->where('vote', false)->count();
        return view('proposals.show', compact('proposal', 'yes', 'no'));
    }

    public function edit(Proposal $proposal)
    {
        return view('proposals.edit', compact('proposal'));
    }

    public function update(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $proposal->update($data);

        return redirect()->route('proposals.show', $proposal);
    }

    public function destroy(Proposal $proposal)
    {
        $property = $proposal->property;
        $proposal->delete();
        return redirect()->route('proposals.index', $property);
    }

    public function vote(Request $request, Proposal $proposal)
    {
        $property = $proposal->property;
        if (! $property->investments()->where('user_id', auth()->id())->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'vote' => 'required|boolean',
        ]);

        Vote::updateOrCreate([
            'user_id' => auth()->id(),
            'proposal_id' => $proposal->id,
        ], [
            'vote' => $validated['vote'],
        ]);

        return redirect()->route('proposals.show', $proposal);
    }
}

