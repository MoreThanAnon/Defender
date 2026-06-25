<?php

namespace App\Actions\Offerings;

use App\Models\Bid;
use App\Models\EquityClass;
use App\Models\Offering;
use App\Models\User;
use App\Events\OfferingSettled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResolveOfferingOutcomeAction
{
    use RecordsFundingEvents;

    public function __construct(
        private CheckSettlementEligibilityAction $eligibility = new CheckSettlementEligibilityAction(),
        private AllocateAcceptedBidAmountsAction $acceptedAmounts = new AllocateAcceptedBidAmountsAction(),
        private AllocateEquityPositionsAction $equity = new AllocateEquityPositionsAction(),
        private CreateLoanNotesAction $loanNotes = new CreateLoanNotesAction(),
        private CreateRefundRecordsAction $refunds = new CreateRefundRecordsAction(),
        private SyncOfferingGovernanceToDecidimAction $governance = new SyncOfferingGovernanceToDecidimAction(),
        private SyncInvestmentsFromOfferingAction $investments = new SyncInvestmentsFromOfferingAction(),
    ) {}

    public function handle(Offering $offering, ?int $equityClassId = null, ?int $orgUserId = null): Offering
    {
        $resolved = DB::transaction(function () use ($offering, $equityClassId, $orgUserId) {
            $locked = Offering::whereKey($offering->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['SETTLED', 'FAILED', 'REFUND_PENDING', 'REFUNDED', 'CANCELLED'], true)) {
                return $locked;
            }

            $eligibility = $this->eligibility->handle($locked);
            if (! $eligibility['eligible']) {
                return $this->failOffering($locked, $eligibility);
            }

            $this->recordFundingEvent($locked, 'offering.closed', [
                'metadata' => ['idempotency_key' => 'offering:closed'],
            ]);

            $locked->forceFill(['status' => 'SETTLING'])->save();
            $this->recordFundingEvent($locked, 'offering.settlement_started', [
                'metadata' => ['idempotency_key' => 'offering:settlement_started'],
            ]);

            $allocation = $this->acceptedAmounts->handle($locked);
            $classId = $equityClassId ?: $this->equityClassId($locked);
            $equityResult = $this->equity->handle($locked->fresh(), $classId, $orgUserId ?: $this->orgStewardUserId());
            $this->loanNotes->handle($locked->fresh(), $equityResult['excess_loan_cents'] ?? []);
            $this->refunds->handle($locked->fresh(), $allocation['refunds']);
            $this->governance->handle($locked->fresh());
            $this->investments->handle($locked->fresh());

            $locked->forceFill(['status' => 'SETTLED', 'resolved_at' => now()])->save();
            $this->recordFundingEvent($locked, 'offering.settled', [
                'metadata' => ['idempotency_key' => 'offering:settled'],
            ]);

            return $locked->fresh();
        });
        if ($resolved->status === 'SETTLED') event(new OfferingSettled($resolved));
        return $resolved;
    }

    private function failOffering(Offering $offering, array $eligibility): Offering
    {
        $captured = $offering->bids()->whereIn('status', ['ACCEPTED', 'accepted'])->exists();

        $offering->forceFill([
            'status' => $captured ? 'REFUND_PENDING' : 'FAILED',
            'failure_reason' => $eligibility['failure_reason'] ?: 'settlement_error',
            'failure_notes' => json_encode($eligibility['reasons']),
            'resolved_at' => now(),
        ])->save();

        $this->recordFundingEvent($offering, 'offering.failed', [
            'metadata' => [
                'idempotency_key' => 'offering:failed',
                'failure_reason' => $offering->failure_reason,
                'eligibility' => $eligibility,
            ],
        ]);

        $refunds = [];
        foreach ($offering->bids()->whereIn('status', ['PLEDGED', 'ACCEPTED', 'accepted'])->get() as $bid) {
            $amount = (int) ($bid->original_amount_cents ?: round(((float) $bid->amount) * 100));
            $bid->forceFill([
                'original_amount_cents' => $amount,
                'accepted_amount_cents' => 0,
                'refunded_amount_cents' => $captured ? $amount : 0,
                'allocation_status' => $captured ? 'refund_pending' : 'failed_campaign',
            ])->save();
            if ($captured) {
                $refunds[$bid->id] = $amount;
            }
        }

        if ($refunds !== []) {
            $this->refunds->handle($offering, $refunds);
        }

        return $offering->fresh();
    }

    private function equityClassId(Offering $offering): int
    {
        $defaults = ['name' => 'Ordinary A'];
        if (Schema::hasColumn('equity_classes', 'hotel_id')) {
            $defaults['hotel_id'] = $offering->property_id;
        }

        return EquityClass::firstOrCreate(
            ['property_id' => $offering->property_id, 'code' => 'ORD-A'],
            $defaults
        )->id;
    }

    private function orgStewardUserId(): ?int
    {
        $email = config('defender.org_steward.email');
        if (! $email) {
            return null;
        }

        return User::where('email', $email)->value('id');
    }
}
