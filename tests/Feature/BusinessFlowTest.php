<?php

namespace Tests\Feature;

use App\Models\User;
use BusinessCore\Models\AuditEvent;
use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\Business;
use BusinessCore\Models\Membership;
use BusinessCore\Models\PaymentTask;
use BusinessCore\Models\Project;
use BusinessCore\Models\Proposal;
use BusinessCore\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The master plan's "definition of done": import a statement, record a €650
 * repair proposal, approve it, prepare the payment, pay in the bank, import the
 * next statement, reconcile, export a monthly report.
 */
class BusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    private function statement(string $name): UploadedFile
    {
        return new UploadedFile(base_path("tests/Fixtures/$name"), $name, 'text/csv', null, true);
    }

    public function test_repair_job_from_quote_to_reconciled_payment_and_monthly_report(): void
    {
        Storage::fake('local');

        $director = User::factory()->create();
        $finance = User::factory()->create();
        $member = User::factory()->create();

        // Director creates the business; it starts in storage.
        $this->actingAs($director)->post('/businesses', ['name' => 'Project 027 Ltd', 'type' => 'business'])->assertRedirect();
        $business = Business::firstOrFail();
        $this->assertSame('stored', $business->status);
        $base = "/businesses/{$business->slug}";

        Membership::create(['business_id' => $business->id, 'user_id' => $finance->id, 'role' => 'finance']);
        Membership::create(['business_id' => $business->id, 'user_id' => $member->id, 'role' => 'member']);

        // First statement.
        $this->actingAs($finance)->post("$base/bank/import", ['statement' => $this->statement('statement-october.csv')])
            ->assertSessionHasNoErrors();
        $this->assertSame(6, BankTransaction::count());
        $this->assertSame('21400.00', $business->balanceSnapshot()['amount']);
        $this->assertSame('statement', $business->balanceSnapshot()['source']);

        // Project, supplier and a €650 proposal.
        $this->actingAs($director)->post("$base/projects", ['name' => 'Repairs', 'status' => 'active', 'budget_amount' => 3500]);
        $project = Project::firstOrFail();
        $this->actingAs($director)->post("$base/suppliers", ['name' => 'Lee Valley Pumps', 'payment_details' => 'IBAN fake']);
        $supplier = Supplier::firstOrFail();

        $this->actingAs($director)->post("$base/proposals", [
            'title' => 'Replace broken water pump', 'amount' => 650, 'project_id' => $project->id, 'supplier_id' => $supplier->id,
        ])->assertSessionHasNoErrors();
        $proposal = Proposal::firstOrFail();

        // A payment cannot be prepared against an undecided proposal.
        $this->actingAs($finance)->post("$base/payments", ['payee_name' => 'Lee Valley Pumps', 'amount' => 650, 'proposal_id' => $proposal->id])
            ->assertSessionHasErrors('proposal_id');

        // Members vote; only a director can decide.
        $this->actingAs($member)->post("$base/proposals/{$proposal->id}/vote", ['choice' => 'yes'])->assertSessionHasNoErrors();
        $this->actingAs($member)->post("$base/proposals/{$proposal->id}/decision", ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($director)->post("$base/proposals/{$proposal->id}/decision", ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertSame($director->id, $proposal->fresh()->decided_by);

        // Finance prepares the payment (not above the approved amount), pays in the bank, marks it sent.
        $this->actingAs($finance)->post("$base/payments", ['payee_name' => 'Lee Valley Pumps', 'amount' => 700, 'proposal_id' => $proposal->id])
            ->assertSessionHasErrors('amount');
        $this->actingAs($finance)->post("$base/payments", [
            'payee_name' => 'Lee Valley Pumps', 'amount' => 650, 'reference' => 'INV-2041', 'proposal_id' => $proposal->id, 'supplier_id' => $supplier->id,
        ])->assertSessionHasNoErrors();
        $payment = PaymentTask::firstOrFail();
        $this->assertSame($project->id, $payment->project_id);
        $this->actingAs($finance)->post("$base/payments/{$payment->id}/sent");
        $this->assertSame('sent', $payment->fresh()->status);

        // Next statement overlaps the first by one line: that line is skipped, not duplicated.
        $this->actingAs($finance)->post("$base/bank/import", ['statement' => $this->statement('statement-november.csv')])
            ->assertSessionHasNoErrors();
        $this->assertSame(8, BankTransaction::count());

        // Reconcile: wrong amount refused, right amount settles the task and charges the project.
        $fee = BankTransaction::where('description', 'Bank fee')->firstOrFail();
        $this->actingAs($finance)->post("$base/bank/transactions/{$fee->id}/match", ['payment_task_id' => $payment->id])
            ->assertSessionHasErrors('payment_task_id');
        $line = BankTransaction::where('description', 'like', '%PUMPS%')->firstOrFail();
        $this->actingAs($finance)->post("$base/bank/transactions/{$line->id}/match", ['payment_task_id' => $payment->id])
            ->assertSessionHasNoErrors();
        $this->assertSame('reconciled', $payment->fresh()->status);
        $this->assertSame('matched', $line->fresh()->status);
        $this->assertEqualsWithDelta(650.0, $project->paid(), 0.001);

        // Categorise a line; the bank's own fields cannot be changed.
        $this->actingAs($finance)->put("$base/bank/transactions/{$fee->id}", ['category' => 'Bank fees', 'amount' => 0])->assertSessionHasNoErrors();
        $this->assertSame('categorised', $fee->fresh()->status);
        $this->assertSame('-4.50', $fee->fresh()->amount);

        // Monthly report, CSV, sign-off, audit log, full export.
        $period = $line->booked_on->format('Y-m');
        $this->actingAs($finance)->get("$base/reports/monthly/$period")->assertOk()->assertSee('LEE VALLEY PUMPS')->assertSee('not statutory accounts');
        $this->actingAs($finance)->get("$base/reports/monthly/$period/csv")->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($finance)->post("$base/reports/monthly/$period/sign-off")->assertForbidden();
        $this->actingAs($director)->post("$base/reports/monthly/$period/sign-off")->assertSessionHasNoErrors();
        $this->actingAs($director)->get("$base/reports/audit")->assertOk()->assertSee('payment.reconciled');
        $this->actingAs($director)->get("$base/reports/export")->assertOk();
        $this->actingAs($finance)->get("$base/reports/export")->assertForbidden();

        $this->assertTrue(AuditEvent::where('action', 'proposal.approved')->exists());

        // Every screen renders for the director.
        foreach (['', '/bank', '/projects', "/projects/{$project->id}", '/proposals', "/proposals/{$proposal->id}", '/payments',
            '/tasks', '/suppliers', '/documents', '/people', '/reports', '/settings'] as $page) {
            $this->actingAs($director)->get($base.$page)->assertOk();
        }
        $this->actingAs($director)->get('/businesses')->assertOk()->assertSee('Project 027 Ltd');
    }

    public function test_storage_to_live_is_done_by_the_platform_admin_and_clears_practice_bank_data(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $owner = User::factory()->create();

        // Admin shelves a business without joining it, then hands it to its owner.
        $this->actingAs($admin)->post('/businesses/admin', ['name' => 'Harbour House', 'type' => 'house'])->assertSessionHasNoErrors();
        $business = Business::firstOrFail();
        $this->assertSame(0, Membership::count());
        $this->actingAs($owner)->get('/businesses/admin')->assertForbidden();
        $this->actingAs($admin)->post("/businesses/admin/{$business->slug}/director", ['email' => $owner->email])->assertSessionHasNoErrors();
        $this->assertSame('director', Membership::firstOrFail()->role);

        $base = "/businesses/{$business->slug}";
        $this->actingAs($owner)->post("$base/bank/import", ['statement' => $this->statement('statement-october.csv')]);
        $this->assertSame(6, BankTransaction::count());

        $live = ['legal_name' => 'Harbour House Ltd', 'registration_number' => '700001', 'bank_name' => 'AIB', 'confirmed' => '1', 'clear_sandbox' => '1'];

        // The owner cannot mark their own business live; the admin can, but only with the confirmation ticked.
        $this->actingAs($owner)->post("$base/go-live", $live)->assertForbidden();
        $this->actingAs($admin)->post("$base/go-live", ['confirmed' => null] + $live)->assertSessionHasErrors('confirmed');
        $this->actingAs($admin)->post("$base/go-live", $live)->assertSessionHasNoErrors();

        $business->refresh();
        $this->assertSame('live', $business->status);
        $this->assertSame('Harbour House Ltd', $business->legal_name);
        $this->assertSame(0, BankTransaction::count());

        // Support access is read-only: the admin can look but not import.
        $this->actingAs($admin)->get("$base/bank")->assertOk();
        $this->actingAs($admin)->post("$base/bank/import", ['statement' => $this->statement('statement-october.csv')])->assertForbidden();

        // Archived businesses are read-only for their own director too.
        $this->actingAs($admin)->post("$base/status", ['status' => 'archived'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("$base/projects", ['name' => 'X', 'status' => 'active'])->assertForbidden();
        $this->actingAs($owner)->get($base)->assertOk();
    }

    public function test_invitation_only_works_for_the_invited_email(): void
    {
        $director = User::factory()->create();
        $invited = User::factory()->create(['email' => 'aoife@example.com']);
        $other = User::factory()->create();

        $this->actingAs($director)->post('/businesses', ['name' => 'Co-op', 'type' => 'business']);
        $business = Business::firstOrFail();
        $this->actingAs($director)->post("/businesses/{$business->slug}/people/invite", ['email' => 'aoife@example.com', 'role' => 'operator'])
            ->assertSessionHasNoErrors();
        $token = \BusinessCore\Models\Invitation::firstOrFail()->token;

        $this->actingAs($other)->post("/businesses/invitations/$token")->assertForbidden();
        $this->actingAs($invited)->get("/businesses/invitations/$token")->assertOk();
        $this->actingAs($invited)->post("/businesses/invitations/$token")->assertRedirect("/businesses/{$business->slug}");
        $this->assertSame('operator', Membership::where('user_id', $invited->id)->firstOrFail()->role);

        // Used once.
        $this->actingAs($invited)->get("/businesses/invitations/$token")->assertNotFound();

        // The last director cannot be demoted.
        $own = Membership::where('user_id', $director->id)->firstOrFail();
        $this->actingAs($director)->put("/businesses/{$business->slug}/people/{$own->id}", ['role' => 'member'])->assertSessionHasErrors('role');
    }
}
