<?php

namespace BusinessCore\Models;

/**
 * An internal motion: discuss, vote, then an authorised person records the decision.
 * The vote is advisory workflow evidence, not a statutory company resolution.
 */
class Proposal extends TenantModel
{
    protected $table = 'business_proposals';

    protected $casts = ['amount' => 'decimal:2', 'closes_at' => 'datetime', 'decided_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function votes()
    {
        return $this->hasMany(ProposalVote::class);
    }

    public function comments()
    {
        return $this->hasMany(ProposalComment::class)->oldest();
    }

    public function paymentTasks()
    {
        return $this->hasMany(PaymentTask::class);
    }

    public function author()
    {
        return $this->userRelation('created_by');
    }

    public function decider()
    {
        return $this->userRelation('decided_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function votingOpen(): bool
    {
        return $this->isOpen() && ($this->closes_at === null || $this->closes_at->isFuture());
    }

    /**
     * @return array{yes:int,no:int,abstain:int,cast:int,eligible:int,quorum_needed:int,quorum_met:bool}
     */
    public function tally(): array
    {
        $counts = $this->votes()->selectRaw('choice, count(*) as c')->groupBy('choice')->pluck('c', 'choice');

        $votingRoles = collect(config('business-core.roles'))
            ->filter(fn ($r) => array_intersect(['*', 'vote'], $r['abilities']))
            ->keys();
        $eligible = Membership::withoutGlobalScope('business')
            ->where('business_id', $this->business_id)->whereIn('role', $votingRoles)->count();

        $cast = (int) $counts->sum();
        $needed = (int) ceil($eligible * ((int) $this->business->quorum_percent) / 100);

        return [
            'yes' => (int) ($counts['yes'] ?? 0),
            'no' => (int) ($counts['no'] ?? 0),
            'abstain' => (int) ($counts['abstain'] ?? 0),
            'cast' => $cast,
            'eligible' => $eligible,
            'quorum_needed' => $needed,
            'quorum_met' => $eligible > 0 && $cast >= $needed,
        ];
    }
}
