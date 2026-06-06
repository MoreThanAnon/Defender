<?php

namespace App\Actions\Offerings;

use App\Models\DecidimGovernanceSync;
use App\Models\Offering;
use Illuminate\Support\Carbon;

class SyncOfferingGovernanceToDecidimAction
{
    use RecordsFundingEvents;

    public function handle(Offering $offering): array
    {
        $payloads = [];
        $now = Carbon::now();

        foreach ($offering->equityPositions()->get() as $position) {
            $role = $position->position_type === 'ORG_RESERVE' ? 'org_steward' : 'local_equity_voter';
            $canVote = $role === 'local_equity_voter' && (bool) $position->can_vote;
            $payloads[] = $this->upsert($offering, $position->holder_user_id, $role, $canVote, (int) $position->voting_bp, $canVote ? 'ordinary_property_control' : 'reserved_matter_only', $now);
        }

        foreach ($offering->loanNotes()->get() as $note) {
            $payloads[] = $this->upsert($offering, $note->investor_id ?: $note->lender_user_id, 'non_voting_lender', false, 0, $note->voting_scope ?: 'debt_protective_only', $now);
        }

        $this->recordFundingEvent($offering, 'governance.synced', [
            'metadata' => [
                'idempotency_key' => 'governance:synced',
                'participants' => count($payloads),
                'payload' => $payloads,
            ],
        ]);

        return $payloads;
    }

    private function upsert(Offering $offering, ?int $investorId, string $role, bool $canVote, int $votingBp, string $scope, Carbon $now): array
    {
        $payload = [
            'offering_id' => $offering->id,
            'investor_id' => $investorId,
            'decidim_user_id' => $investorId ? (string) $investorId : null,
            'role' => $role,
            'can_vote' => $canVote,
            'voting_bp' => $votingBp,
            'voting_scope' => $scope,
            'synced_at' => $now,
        ];

        DecidimGovernanceSync::updateOrCreate(
            ['offering_id' => $offering->id, 'investor_id' => $investorId, 'role' => $role],
            $payload + ['payload' => $payload]
        );

        return $payload;
    }
}
