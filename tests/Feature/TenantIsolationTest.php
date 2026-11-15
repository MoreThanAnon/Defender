<?php

namespace Tests\Feature;

use App\Models\User;
use BusinessCore\Models\Business;
use BusinessCore\Models\Document;
use BusinessCore\Models\PaymentTask;
use BusinessCore\Models\Project;
use BusinessCore\Models\Proposal;
use BusinessCore\Models\Supplier;
use BusinessCore\Services\BusinessLifecycle;
use BusinessCore\Services\StatementImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** No user can see or touch another company's business, transactions or files. */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;
    private User $bob;
    private Business $a;
    private Business $b;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();
        $lifecycle = app(BusinessLifecycle::class);
        $this->a = $lifecycle->create(['name' => 'Alpha', 'type' => 'business'], $this->alice);
        $this->b = $lifecycle->create(['name' => 'Bravo', 'type' => 'land'], $this->bob);
    }

    public function test_outsiders_get_404_on_every_screen_of_a_business(): void
    {
        foreach (['', '/bank', '/projects', '/proposals', '/payments', '/tasks', '/suppliers', '/documents', '/people', '/reports',
            '/reports/audit', '/reports/export', '/settings'] as $page) {
            $this->actingAs($this->alice)->get("/businesses/{$this->b->slug}$page")->assertNotFound();
        }
        $this->actingAs($this->alice)->get('/businesses')->assertOk()->assertSee('Alpha')->assertDontSee('Bravo');
        $this->get("/businesses/{$this->b->slug}")->assertNotFound();
    }

    public function test_guests_are_sent_to_login(): void
    {
        auth()->logout();
        $this->get('/businesses')->assertRedirect('/login');
        $this->get("/businesses/{$this->a->slug}/bank")->assertRedirect('/login');
    }

    public function test_another_business_records_cannot_be_reached_through_my_own_business_url(): void
    {
        $bobProject = Project::create(['business_id' => $this->b->id, 'name' => 'Bob secret project', 'status' => 'active']);
        $bobProposal = Proposal::create(['business_id' => $this->b->id, 'title' => 'Bob secret proposal']);
        $bobSupplier = Supplier::create(['business_id' => $this->b->id, 'name' => 'Bob supplier', 'payment_details' => 'IBAN-BOB']);
        $bobPayment = PaymentTask::create(['business_id' => $this->b->id, 'payee_name' => 'X', 'amount' => 10]);
        $bobImport = app(StatementImporter::class)->import($this->b, base_path('tests/Fixtures/statement-october.csv'), 'oct.csv', $this->bob);
        $bobLine = $bobImport->transactions()->firstOrFail();
        Storage::disk('local')->put('business-core/b/doc.pdf', 'secret');
        $bobDocument = Document::create(['business_id' => $this->b->id, 'title' => 'Bob minutes', 'kind' => 'minutes',
            'path' => 'business-core/b/doc.pdf', 'original_name' => 'doc.pdf', 'sha256' => str_repeat('0', 64)]);

        $mine = "/businesses/{$this->a->slug}";
        $this->actingAs($this->alice);

        // Reading
        $this->get("$mine/projects/{$bobProject->id}")->assertNotFound();
        $this->get("$mine/proposals/{$bobProposal->id}")->assertNotFound();
        $this->get("$mine/documents/{$bobDocument->id}")->assertNotFound();
        $this->get("$mine/bank/imports/{$bobImport->id}/file")->assertNotFound();

        // Writing
        $this->put("$mine/projects/{$bobProject->id}", ['name' => 'hacked', 'status' => 'done'])->assertNotFound();
        $this->put("$mine/suppliers/{$bobSupplier->id}", ['name' => 'hacked', 'payment_details' => 'IBAN-ALICE'])->assertNotFound();
        $this->put("$mine/bank/transactions/{$bobLine->id}", ['category' => 'hacked'])->assertNotFound();
        $this->post("$mine/payments/{$bobPayment->id}/sent")->assertNotFound();
        $this->post("$mine/proposals/{$bobProposal->id}/vote", ['choice' => 'yes'])->assertNotFound();
        $this->post("$mine/proposals/{$bobProposal->id}/decision", ['decision' => 'approved'])->assertNotFound();

        // Linking my records to theirs through form fields
        $this->post("$mine/proposals", ['title' => 'Mine', 'project_id' => $bobProject->id])->assertSessionHasErrors('project_id');
        $this->post("$mine/payments", ['payee_name' => 'Y', 'amount' => 5, 'supplier_id' => $bobSupplier->id])->assertSessionHasErrors('supplier_id');
        $this->post("$mine/tasks", ['title' => 'T', 'status' => 'todo', 'assigned_to' => $this->bob->id])->assertSessionHasErrors('assigned_to');

        $this->assertSame('Bob secret project', $bobProject->fresh()->name);
        $this->assertSame('IBAN-BOB', $bobSupplier->fresh()->payment_details);
        $this->assertNull($bobLine->fresh()->category);
        $this->assertSame('prepared', $bobPayment->fresh()->status);

        // And my own lists never include their rows.
        $this->get("$mine/projects")->assertOk()->assertDontSee('Bob secret project');
        $this->get("$mine/suppliers")->assertOk()->assertDontSee('Bob supplier');
    }

    public function test_roles_limit_what_members_can_do(): void
    {
        $member = User::factory()->create();
        $auditor = User::factory()->create();
        \BusinessCore\Models\Membership::create(['business_id' => $this->a->id, 'user_id' => $member->id, 'role' => 'member']);
        \BusinessCore\Models\Membership::create(['business_id' => $this->a->id, 'user_id' => $auditor->id, 'role' => 'auditor']);
        $base = "/businesses/{$this->a->slug}";

        // Member: sees the dashboard, not the bank; cannot change anything but a vote.
        $this->actingAs($member)->get($base)->assertOk();
        $this->actingAs($member)->get("$base/bank")->assertForbidden();
        $this->actingAs($member)->get("$base/reports")->assertForbidden();
        $this->actingAs($member)->post("$base/projects", ['name' => 'P', 'status' => 'active'])->assertForbidden();
        $this->actingAs($member)->post("$base/people/invite", ['email' => 'x@example.com', 'role' => 'director'])->assertForbidden();
        $this->actingAs($member)->put("$base/settings", ['name' => 'Renamed', 'type' => 'business', 'quorum_percent' => 50])->assertForbidden();

        // Auditor: reads finance, changes nothing.
        $this->actingAs($auditor)->get("$base/bank")->assertOk();
        $this->actingAs($auditor)->get("$base/reports/audit")->assertOk();
        $this->actingAs($auditor)->post("$base/payments", ['payee_name' => 'Z', 'amount' => 1])->assertForbidden();
        $this->actingAs($auditor)->post("$base/proposals", ['title' => 'No'])->assertForbidden();
    }

    public function test_audit_history_and_bank_lines_are_tamper_resistant(): void
    {
        $import = app(StatementImporter::class)->import($this->a, base_path('tests/Fixtures/statement-october.csv'), 'oct.csv', $this->alice);
        $line = $import->transactions()->firstOrFail();

        try {
            $line->update(['amount' => '1.00']);
            $this->fail('A bank line was edited.');
        } catch (\LogicException) {
        }

        $event = \BusinessCore\Models\AuditEvent::firstOrFail();
        foreach ([fn () => $event->update(['action' => 'x']), fn () => $event->delete()] as $attempt) {
            try {
                $attempt();
                $this->fail('An audit event was changed.');
            } catch (\LogicException) {
            }
        }
        $this->assertTrue(true);
    }
}
