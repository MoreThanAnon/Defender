<?php

namespace BusinessCore\Services;

use BusinessCore\Contracts\AuditRecorder;
use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\Business;
use BusinessCore\Models\Membership;
use BusinessCore\Models\PaymentTask;
use BusinessCore\Models\StatementImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Creating businesses and moving them between storage and live.
 *
 * "Stored" businesses are sandboxes: set up, try out, or keep on the shelf.
 * Going live records the real company and bank this business now represents.
 * Nothing here opens a bank connection; it is a record that a verified person made the link.
 */
class BusinessLifecycle
{
    public function __construct(private AuditRecorder $audit)
    {
    }

    public function create(array $attributes, mixed $creator, bool $creatorIsDirector = true): Business
    {
        return DB::transaction(function () use ($attributes, $creator, $creatorIsDirector) {
            $business = Business::create(array_merge($attributes, [
                'slug' => Business::makeSlug($attributes['name']),
                'status' => Business::STORED,
                'created_by' => $creator?->getKey(),
            ]));

            if ($creator && $creatorIsDirector) {
                Membership::create(['business_id' => $business->id, 'user_id' => $creator->getKey(), 'role' => 'director']);
            }

            $this->audit->record('business.created', $business, ['type' => $business->type], $business);

            return $business;
        });
    }

    /**
     * @param array{legal_name:string, registration_number:?string, bank_name:string, bank_url:?string, bank_account_label:?string} $details
     */
    public function goLive(Business $business, array $details, mixed $actor, bool $clearSandboxBankData = false): void
    {
        DB::transaction(function () use ($business, $details, $actor, $clearSandboxBankData) {
            if ($clearSandboxBankData) {
                $this->clearBankData($business);
            }

            $business->update(array_merge($details, [
                'status' => Business::LIVE,
                'went_live_at' => now(),
                'went_live_by' => $actor?->getKey(),
            ]));

            $this->audit->record('business.went_live', $business, [
                'legal_name' => $details['legal_name'], 'sandbox_bank_data_cleared' => $clearSandboxBankData,
            ], $business);
        });
    }

    public function setStatus(Business $business, string $status): void
    {
        $from = $business->status;
        $business->update(['status' => $status]);
        $this->audit->record('business.status_changed', $business, ['from' => $from, 'to' => $status], $business);
    }

    /** Make a person a director (used to hand a stored business to its real owner). */
    public function assignDirector(Business $business, mixed $user): void
    {
        Membership::withoutGlobalScope('business')->updateOrCreate(
            ['business_id' => $business->id, 'user_id' => $user->getKey()],
            ['role' => 'director'],
        );
        $this->audit->record('member.director_assigned', $business, ['user_id' => $user->getKey()], $business);
    }

    /** Remove practice statements so a live business starts from real bank data only. */
    private function clearBankData(Business $business): void
    {
        $imports = StatementImport::withoutGlobalScope('business')->where('business_id', $business->id)->get();

        BankTransaction::withoutGlobalScope('business')->where('business_id', $business->id)->delete();
        PaymentTask::withoutGlobalScope('business')->where('business_id', $business->id)->where('status', 'reconciled')
            ->update(['status' => 'sent', 'reconciled_at' => null]);

        foreach ($imports as $import) {
            Storage::disk(config('business-core.disk'))->delete($import->path);
            $import->delete();
        }
    }
}
