<?php

namespace Tests\Feature;

use App\Actions\Offerings\ResolveOfferingOutcomeAction;
use App\Models\Bid;
use App\Models\DecidimGovernanceSync;
use App\Models\DecidimGovernanceSpace;
use App\Models\EquityPosition;
use App\Models\FundingEvent;
use App\Models\Investment;
use App\Models\LoanNote;
use App\Models\Offering;
use App\Models\Property;
use App\Models\RefundRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResolveOfferingOutcomeActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['decidim.enabled' => false]);

        DB::table('properties_status')->insertOrIgnore([
            ['id' => '1', 'status' => 'Active', 'description' => 'Test active status'],
            ['id' => '2', 'status' => 'Inactive', 'description' => 'Test inactive status'],
        ]);
    }

    public function test_target_missed_fails(): void
    {
        $offering = $this->offering(target: 100000, localCount: 1);
        $this->bid($offering, 100, 40000, true);

        $resolved = $this->resolve($offering);

        $this->assertSame('FAILED', $resolved->status);
        $this->assertSame('under_target', $resolved->failure_reason);
    }

    public function test_target_met_but_local_capital_floor_missed_fails(): void
    {
        $offering = $this->offering(target: 100000, localCount: 1);
        $this->bid($offering, 100, 20000, true);
        $this->bid($offering, 101, 80000, false);

        $resolved = $this->resolve($offering);

        $this->assertSame('FAILED', $resolved->status);
        $this->assertSame('below_local_capital_floor', $resolved->failure_reason);
    }

    public function test_target_met_but_local_investor_count_missed_fails(): void
    {
        $offering = $this->offering(target: 100000, localCount: 3);
        $this->bid($offering, 100, 60000, true);
        $this->bid($offering, 101, 40000, false);

        $resolved = $this->resolve($offering);

        $this->assertSame('FAILED', $resolved->status);
        $this->assertSame('below_local_investor_floor', $resolved->failure_reason);
    }

    public function test_valid_local_and_non_local_mixed_raise_settles(): void
    {
        $offering = $this->offering(target: 100000, localCount: 2);
        $this->bid($offering, 100, 25000, true);
        $this->bid($offering, 101, 25000, true);
        $this->bid($offering, 102, 50000, false);

        $resolved = $this->resolve($offering);

        $this->assertSame('SETTLED', $resolved->status);
        $this->assertSame(2, EquityPosition::where('position_type', 'LOCAL_INVESTOR')->where('can_vote', true)->count());
        $this->assertSame(3, LoanNote::where('voting_scope', 'debt_protective_only')->count());
        $this->assertSame(5, Investment::count());
    }

    public function test_overfunded_raise_scales_non_local_bucket_and_creates_refunds(): void
    {
        $offering = $this->offering(target: 100000, localCount: 2);
        $this->bid($offering, 100, 50000, true);
        $this->bid($offering, 101, 50000, true);
        $this->bid($offering, 102, 100000, false);

        $this->resolve($offering);

        $this->assertSame(0, Bid::where('user_id', 102)->value('accepted_amount_cents'));
        $this->assertSame(100000, RefundRecord::where('user_id', 102)->value('amount_cents'));
    }

    public function test_single_local_investor_above_voting_cap_gets_capped_equity_and_loan_note_for_excess(): void
    {
        $offering = $this->offering(target: 100000, localPct: 0, localCount: 1);
        $this->bid($offering, 100, 100000, true);

        $this->resolve($offering);

        $position = EquityPosition::where('holder_user_id', 100)->firstOrFail();
        $this->assertSame(1500, $position->voting_bp);
        $this->assertSame(1500, $position->economic_bp);
        $this->assertSame(83334, LoanNote::where('investor_id', 100)->value('principal_cents'));
    }

    public function test_all_non_local_bids_fail_when_local_threshold_required(): void
    {
        $offering = $this->offering(target: 100000, localCount: 1);
        $this->bid($offering, 100, 100000, false);

        $resolved = $this->resolve($offering);

        $this->assertSame('FAILED', $resolved->status);
        $this->assertSame('below_local_capital_floor', $resolved->failure_reason);
    }

    public function test_treasury_does_not_vote(): void
    {
        $offering = $this->offering(target: 100000, localPct: 0, localCount: 1);
        $this->bid($offering, 100, 100000, true);

        $this->resolve($offering);

        $treasury = EquityPosition::where('position_type', 'TREASURY')->firstOrFail();
        $this->assertFalse($treasury->can_vote);
        $this->assertSame(0, $treasury->voting_bp);
    }

    public function test_non_local_lenders_do_not_receive_ordinary_voting_rights(): void
    {
        $offering = $this->offering(target: 100000, localCount: 1);
        $this->bid($offering, 100, 25000, true);
        $this->bid($offering, 101, 75000, false);

        $this->resolve($offering);

        $sync = DecidimGovernanceSync::where('investor_id', 101)->where('role', 'non_voting_lender')->firstOrFail();
        $this->assertFalse($sync->can_vote);
        $this->assertSame(0, $sync->voting_bp);
    }

    public function test_non_local_lenders_receive_debt_protective_scope_only(): void
    {
        $offering = $this->offering(target: 100000, localCount: 1);
        $this->bid($offering, 100, 25000, true);
        $this->bid($offering, 101, 75000, false);

        $this->resolve($offering);

        $this->assertSame('debt_protective_only', LoanNote::where('investor_id', 101)->value('voting_scope'));
    }

    public function test_repeated_resolver_runs_do_not_duplicate_records(): void
    {
        $offering = $this->offering(target: 100000, localCount: 1);
        $this->bid($offering, 100, 25000, true);
        $this->bid($offering, 101, 100000, false);

        $this->resolve($offering);
        $counts = [
            'equity' => EquityPosition::count(),
            'loan_notes' => LoanNote::count(),
            'refunds' => RefundRecord::count(),
            'events' => FundingEvent::count(),
        ];
        $this->resolve($offering->fresh());

        $this->assertSame($counts['equity'], EquityPosition::count());
        $this->assertSame($counts['loan_notes'], LoanNote::count());
        $this->assertSame($counts['refunds'], RefundRecord::count());
        $this->assertSame($counts['events'], FundingEvent::count());
    }

    public function test_funded_hotel_creates_decidim_assembly_and_default_components(): void
    {
        config(['decidim.enabled' => true, 'decidim.dry_run' => false, 'decidim.base_url' => 'https://decidim.test', 'decidim.api_token' => 'test-token', 'decidim.organization_id' => 'org_1']);
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, '/users')) return Http::response(['decidim_user_id' => 'decidim_user_'.$request['laravel_user_id'], 'created' => true]);
            if (str_contains($url, '/members')) return Http::response(['id' => 'member_'.$request['laravel_user_id'], 'decidim_user_id' => 'decidim_user_'.$request['laravel_user_id']]);
            if (str_contains($url, '/taxonomies')) return Http::response(['id' => 'taxonomy_1']);
            if (str_contains($url, '/debates')) return Http::response(['id' => 'debate_1']);
            if (str_contains($url, '/proposals')) return Http::response(['id' => 'proposal_1']);
            if (str_contains($url, '/meetings')) return Http::response(['id' => 'meeting_1']);
            if (str_contains($url, '/components')) return Http::response(['id' => 'component_'.count(Http::recorded())]);
            return Http::response(['id' => 'assembly_123', 'slug' => 'hotel-1-test-property', 'url' => 'https://decidim.test/assemblies/hotel-1-test-property']);
        });
        $offering = $this->offering(target: 100000, localPct: 0, localCount: 1);
        $this->bid($offering, 100, 50000, true);
        $this->bid($offering, 101, 50000, false);

        $resolved = $this->resolve($offering);
        $space = DecidimGovernanceSpace::where('offering_id', $offering->id)->firstOrFail();

        $this->assertSame('SETTLED', $resolved->status);
        $this->assertSame('created', $space->status);
        $this->assertSame('assembly_123', $space->decidim_assembly_id);
        $this->assertNotNull($space->decidim_debates_component_id);
        $this->assertNotNull($space->decidim_proposals_component_id);
        $this->assertNotNull($space->decidim_meetings_component_id);
        $this->assertNotNull($space->decidim_voting_component_id);
        $this->assertSame(5, DecidimGovernanceSync::count());
        $this->assertDatabaseHas('decidim_governance_syncs', ['investor_id' => 100, 'role' => 'local_equity_voter', 'can_vote' => true]);
        $this->assertDatabaseHas('decidim_governance_syncs', ['investor_id' => 100, 'role' => 'non_voting_lender', 'can_vote' => false]);
        $this->assertDatabaseHas('decidim_governance_syncs', ['investor_id' => 101, 'role' => 'non_voting_lender', 'can_vote' => false]);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/users') && $request['email'] && $request['name'] && $request['laravel_user_id'] === '100');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/members') && $request['role'] === 'local_equity_voter' && $request['metadata']['can_vote'] === true);
        Http::assertSentCount(14);
        $this->resolve($resolved->fresh());
        $this->assertSame(1, DecidimGovernanceSpace::count());
        $this->assertSame(14, Http::recorded()->count());
    }

    private function offering(int $target, int $localPct = 25, int $localCount = 3): Offering
    {
        $property = Property::factory()->create();

        return Offering::create([
            'property_id' => $property->id,
            'name' => 'Test offering',
            'status' => 'CLOSED',
            'target_raise' => number_format($target / 100, 2, '.', ''),
            'target_raise_cents' => $target,
            'hard_cap_cents' => $target,
            'org_reserve_pct' => '10.00',
            'min_local_capital_pct' => $localPct,
            'min_local_investor_count' => $localCount,
            'require_local_threshold_for_settlement' => true,
            'campaign_end' => now()->subDay(),
        ]);
    }

    private function bid(Offering $offering, int $userId, int $amountCents, bool $local): Bid
    {
        User::factory()->create(['id' => $userId, 'username' => "user{$userId}"]);

        return Bid::create([
            'property_id' => $offering->property_id,
            'offering_id' => $offering->id,
            'user_id' => $userId,
            'amount' => number_format($amountCents / 100, 2, '.', ''),
            'original_amount_cents' => $amountCents,
            'is_local' => $local,
            'is_local_at_bid_time' => $local,
            'status' => 'PLEDGED',
        ]);
    }

    private function resolve(Offering $offering): Offering
    {
        return (new ResolveOfferingOutcomeAction())->handle($offering);
    }
}
