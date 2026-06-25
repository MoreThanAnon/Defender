<?php

namespace App\Services\Decidim;

use App\Models\DecidimGovernanceSpace;
use App\Models\DecidimGovernanceSync;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class DecidimGovernanceService
{
    public function __construct(private DecidimClient $client) {}

    public function createGovernanceForSettledOffering(Offering $offering): DecidimGovernanceSpace
    {
        if ($offering->status !== 'SETTLED') throw new RuntimeException('Only settled offerings can create governance spaces.');
        $space = DecidimGovernanceSpace::firstOrCreate(['offering_id' => $offering->id], ['property_id' => $offering->property_id, 'status' => 'pending']);
        if ($space->status === 'created') return $space;
        if (! config('decidim.enabled')) return tap($space)->update(['status' => 'pending']);

        try {
            $space->forceFill([
                'status' => 'syncing',
                'retry_count' => (int) $space->retry_count + 1,
                'last_attempted_at' => now(),
            ])->save();

            $asset = $offering->property;
            $slug = 'asset-'.$offering->id.'-'.Str::slug($asset->name);
            $assembly = $space->decidim_assembly_id ? [] : $this->client->createAssembly([
                'organization_id' => config('decidim.organization_id'), 'title' => "Governance Assembly - {$asset->name}", 'slug' => $slug,
                'subtitle' => "Investor governance space for {$asset->name}",
                'description' => "This private Assembly is the governance home for eligible {$asset->name} investors after settlement. Use Discussions to explore issues, Investor Proposals to turn ideas into formal decisions, Investor Meetings to organise review sessions, and Investor Votes for formal approvals. Finance, maintenance, operations, tenancy/lease status, land or business due diligence, compliance, and bank-balance reporting are the initial governance areas.",
                'private' => true,
            ]);
            if ($assembly) $space->update(['decidim_assembly_id' => $assembly['id'], 'decidim_assembly_slug' => $assembly['slug'] ?? $slug, 'decidim_assembly_url' => $assembly['url'] ?? null]);
            $assemblyId = $space->fresh()->decidim_assembly_id;
            $this->members($offering, $assemblyId);
            $taxonomy = $this->client->createTaxonomy(['name' => 'Asset Governance', 'description' => 'Use these categories to make decisions traceable and ready for future dashboards of costs due, bank balance, maintenance, due diligence, revenue, tenancy/lease status, and investor decisions.', 'terms' => ['Finance','Maintenance and Tasks','Operations','Tenancy and Lease','Land and Planning','Legal and Compliance','Investor Votes','Meetings'], 'assembly_id' => $assemblyId]);
            $debates = $this->component($assemblyId, 'debates', 'Discussions', 'Start an informal investor discussion here before proposing a decision. Keep posts factual, tag the relevant governance area, and use comments to gather evidence and views.');
            $proposals = $this->component($assemblyId, 'proposals', 'Investor Proposals', 'Use this for a specific action that needs investor support or approval. State the decision requested, costs, owner, timing, supporting documents, and the effect of doing nothing.');
            $meetings = $this->component($assemblyId, 'meetings', 'Investor Meetings', 'Use this to schedule governance meetings. Publish an agenda, attach briefing papers in advance, record decisions and actions afterwards, and link any follow-up proposals.');
            $votes = $this->client->createVoteOrElectionComponent($assemblyId, ['type' => 'voting', 'name' => 'Investor Votes', 'description' => 'Use this component for formal Assembly decisions after discussion and a clear proposal. Weighted voting by investment share is intentionally not enabled yet; implement it here only when the chosen Decidim voting module supports it.', 'settings' => ['weighted_voting_todo' => true]]);
            $this->client->createDebate($debates['id'], ['title' => "Welcome discussion - {$asset->name} is now fully funded", 'body' => "Settlement is complete. Use this opening discussion to identify the first priorities: urgent tasks, bills or costs due, operating costs, bank-account visibility, tenancy/lease or due-diligence status, and the agenda for the first investor meeting. This is for exploration; move a mature idea to Investor Proposals when it needs a formal decision."]);
            $this->client->createProposal($proposals['id'], ['title' => 'Prepare first 90-day asset operating plan', 'body' => 'Request: create and publish a 90-day operating plan. It should list immediate tasks, outstanding costs, expected running costs, current and forecast bank balance, reporting dates, accountable owners, and risks requiring an investor decision. Investors should review the attached evidence and support, oppose, or suggest amendments in the comments before a formal vote.']);
            $this->client->createMeeting($meetings['id'], ['title' => "First investor governance meeting - {$asset->name}", 'description' => 'Purpose: agree the first 90-day priorities. Agenda: asset condition/status, urgent costs and bills, bank/account visibility, maintenance or due-diligence actions, reporting cadence, and the operating-plan proposal. Investors should read the agenda and papers beforehand, attend online where possible, and record any action or decision in the linked proposal/vote.', 'starts_at' => now()->addDays(14)->toIso8601String(), 'location' => 'Online / Decidim / To be confirmed']);
            $space->update(['decidim_taxonomy_id' => $taxonomy['id'] ?? null, 'decidim_debates_component_id' => $debates['id'], 'decidim_proposals_component_id' => $proposals['id'], 'decidim_meetings_component_id' => $meetings['id'], 'decidim_voting_component_id' => $votes['id'] ?? null, 'status' => 'created', 'last_succeeded_at' => now(), 'error_message' => null]);
        } catch (\Throwable $e) { $space->update(['status' => 'failed', 'last_failed_at' => now(), 'error_message' => $e->getMessage()]); throw $e; }
        return $space->fresh();
    }

    private function component(string $assemblyId, string $type, string $name, string $description): array { return $this->client->createComponent($assemblyId, ['type' => $type, 'name' => $name, 'description' => $description, 'settings' => ['private_participation' => true, 'comments_enabled' => true, 'attachments_enabled' => true]]); }
    private function members(Offering $offering, string $assemblyId): void
    {
        $participants = $this->participants($offering);
        $users = User::whereIn('id', $participants->pluck('user_id')->unique()->values())->get()->keyBy('id');
        $decidimUserIds = [];

        foreach ($users as $user) {
            $response = $this->client->syncUser($this->userPayload($user));
            $decidimUserIds[$user->id] = $response['decidim_user_id'] ?? $response['id'] ?? null;
        }

        foreach ($participants as $participant) {
            $user = $users->get($participant['user_id']);
            if (! $user) {
                continue;
            }

            $payload = [
                'laravel_user_id' => (string) $user->id,
                'role' => $participant['role'],
                'metadata' => $participant['metadata'],
            ];
            $member = $this->client->addAssemblyMember($assemblyId, $payload);

            DecidimGovernanceSync::updateOrCreate(
                [
                    'offering_id' => $offering->id,
                    'investor_id' => $user->id,
                    'role' => $participant['role'],
                ],
                [
                    'decidim_user_id' => (string) ($member['decidim_user_id'] ?? $decidimUserIds[$user->id] ?? ''),
                    'can_vote' => (bool) $participant['metadata']['can_vote'],
                    'voting_bp' => (int) $participant['metadata']['voting_bp'],
                    'voting_scope' => $participant['metadata']['voting_scope'],
                    'synced_at' => now(),
                    'payload' => [
                        'user' => $this->userPayload($user),
                        'membership' => $payload,
                        'decidim_response' => $member,
                    ],
                ]
            );
        }
    }

    private function participants(Offering $offering): Collection
    {
        $participants = collect();

        foreach ($offering->equityPositions as $position) {
            if (! $position->holder_user_id || $position->position_type === 'TREASURY') {
                continue;
            }

            $role = $position->position_type === 'ORG_RESERVE'
                ? 'org_steward'
                : 'local_equity_voter';

            $participants->push([
                'user_id' => (int) $position->holder_user_id,
                'role' => $role,
                'metadata' => [
                    'source' => 'equity_position',
                    'position_type' => $position->position_type,
                    'can_vote' => (bool) $position->can_vote,
                    'voting_bp' => (int) $position->voting_bp,
                    'economic_bp' => (int) $position->economic_bp,
                    'voting_scope' => (bool) $position->can_vote ? 'ordinary_property_control' : 'reserved_matter_only',
                ],
            ]);
        }

        foreach ($offering->loanNotes as $note) {
            $userId = $note->investor_id ?: $note->lender_user_id;
            if (! $userId) {
                continue;
            }

            $participants->push([
                'user_id' => (int) $userId,
                'role' => 'non_voting_lender',
                'metadata' => [
                    'source' => 'loan_note',
                    'can_vote' => false,
                    'voting_bp' => 0,
                    'principal_cents' => (int) $note->principal_cents,
                    'voting_scope' => $note->voting_scope ?: 'debt_protective_only',
                ],
            ]);
        }

        return $participants
            ->filter(fn (array $participant) => $participant['user_id'] > 0)
            ->unique(fn (array $participant) => $participant['user_id'].'|'.$participant['role'])
            ->values();
    }

    private function userPayload(User $user): array
    {
        $name = $user->real_name ?: $user->name ?: $user->username ?: "Investor {$user->id}";

        return [
            'laravel_user_id' => (string) $user->id,
            'email' => $user->email ?: "laravel-user-{$user->id}@example.invalid",
            'name' => $name,
            'nickname' => $user->username ?: Str::slug($name.'-'.$user->id),
        ];
    }
}
