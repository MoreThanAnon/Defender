<?php

namespace Tests\Feature;

use App\Models\AssetOperationsChecklist;
use App\Models\Offering;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOperationsChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_operations_index_and_checklist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $offering = $this->settledOffering();

        $this->actingAs($admin)
            ->get(route('admin.operations.index'))
            ->assertOk()
            ->assertSee('Asset Operations')
            ->assertSee('Legal review pending')
            ->assertSee('With health alerts')
            ->assertSee('Uploaded documents')
            ->assertSee('Documents needing review')
            ->assertSee('Documents reviewed')
            ->assertSee('Documents changes needed')
            ->assertSee('No Decidim governance space')
            ->assertSee('No investor roles synced')
            ->assertSee($offering->name);

        $this->actingAs($admin)
            ->get(route('admin.operations.show', $offering))
            ->assertOk()
            ->assertSee('Operating snapshot')
            ->assertSee('Uploaded asset documents')
            ->assertSee('Required asset documents')
            ->assertSee('Asset identity and description')
            ->assertSee('rental house')
            ->assertSee('shop unit')
            ->assertSee('Legal / compliance review placeholders')
            ->assertSee('Save asset operations checklist');

        $this->assertDatabaseHas('asset_operations_checklists', [
            'offering_id' => $offering->id,
            'property_id' => $offering->property_id,
        ]);
    }

    public function test_admin_can_update_operations_checklist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $offering = $this->settledOffering();

        $this->actingAs($admin)
            ->post(route('admin.operations.update', $offering), [
                'bank_balance' => '12345.67',
                'bills_due_text' => "Electricity invoice due Friday\nInsurance renewal",
                'maintenance_tasks_text' => "Inspect boiler\nRepair roof leak",
                'required_documents' => [
                    'asset_identity' => '1',
                    'ownership_or_title' => '1',
                    'valuation_or_price_support' => '0',
                    'acquisition_agreement' => '1',
                    'income_or_use_plan' => '0',
                    'costs_and_liabilities' => '1',
                    'insurance_and_risk' => '0',
                    'regulatory_permissions' => '0',
                    'governance_and_investor_rights' => '0',
                    'tax_and_structure_review' => '0',
                ],
                'meeting_status' => [
                    'first_meeting_scheduled' => '1',
                    'agenda_published' => '0',
                    'minutes_published' => '0',
                ],
                'proposal_status' => [
                    'operating_plan_proposed' => '1',
                    'maintenance_budget_proposed' => '0',
                    'bank_reporting_proposed' => '1',
                ],
                'legal_review' => [
                    'solicitor_review' => '1',
                    'central_bank_review' => '0',
                    'company_law_review' => '0',
                    'investor_rights_review' => '0',
                    'tax_review' => '0',
                    'property_structure_review' => '0',
                    'data_protection_review' => '0',
                ],
                'admin_notes' => 'Need solicitor review before public claim.',
            ])
            ->assertRedirect(route('admin.operations.show', $offering));

        $checklist = AssetOperationsChecklist::where('offering_id', $offering->id)->firstOrFail();

        $this->assertSame('12345.67', $checklist->bank_balance);
        $this->assertSame('Electricity invoice due Friday', $checklist->bills_due[0]['label']);
        $this->assertTrue($checklist->required_documents['asset_identity']);
        $this->assertTrue($checklist->required_documents['ownership_or_title']);
        $this->assertFalse($checklist->required_documents['valuation_or_price_support']);
        $this->assertTrue($checklist->meeting_status['first_meeting_scheduled']);
        $this->assertTrue($checklist->proposal_status['operating_plan_proposed']);
        $this->assertTrue($checklist->legal_review['solicitor_review']);
        $this->assertFalse($checklist->legal_review['central_bank_review']);
        $this->assertNotNull($checklist->reviewed_at);

        $this->actingAs($admin)
            ->get(route('admin.operations.index'))
            ->assertOk()
            ->assertSee('Asset document pack incomplete');
    }

    public function test_admin_can_upload_and_delete_private_asset_documents(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $offering = $this->settledOffering();
        $file = UploadedFile::fake()->create('valuation.pdf', 256, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('admin.operations.documents.store', $offering), [
                'document_key' => 'valuation_or_price_support',
                'title' => 'Independent valuation',
                'notes' => 'Uploaded for solicitor and accountant review.',
                'document' => $file,
            ])
            ->assertRedirect(route('admin.operations.show', $offering));

        $this->assertDatabaseHas('asset_documents', [
            'offering_id' => $offering->id,
            'property_id' => $offering->property_id,
            'uploaded_by' => $admin->id,
            'document_key' => 'valuation_or_price_support',
            'title' => 'Independent valuation',
            'original_filename' => 'valuation.pdf',
            'review_status' => 'needs_review',
        ]);

        $document = $offering->assetDocuments()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);

        $checklist = AssetOperationsChecklist::where('offering_id', $offering->id)->firstOrFail();
        $this->assertTrue($checklist->required_documents['valuation_or_price_support']);

        $this->actingAs($admin)
            ->get(route('admin.operations.show', $offering))
            ->assertOk()
            ->assertSee('Independent valuation')
            ->assertSee('Needs review')
            ->assertSee('valuation.pdf');

        $this->actingAs($admin)
            ->patch(route('admin.operations.documents.update', [$offering, $document]), [
                'review_status' => 'reviewed',
                'notes' => 'Accountant reviewed the valuation support.',
            ])
            ->assertRedirect(route('admin.operations.show', $offering));

        $document->refresh();
        $this->assertSame('reviewed', $document->review_status);
        $this->assertSame('Accountant reviewed the valuation support.', $document->notes);
        $this->assertNotNull($document->reviewed_at);

        $this->actingAs($admin)
            ->patch(route('admin.operations.documents.update', [$offering, $document]), [
                'review_status' => 'changes_needed',
                'notes' => 'Need a signed valuation letter.',
            ])
            ->assertRedirect(route('admin.operations.show', $offering));

        $document->refresh();
        $this->assertSame('changes_needed', $document->review_status);
        $this->assertSame('Need a signed valuation letter.', $document->notes);
        $this->assertNull($document->reviewed_at);

        $this->actingAs($admin)
            ->get(route('admin.operations.index'))
            ->assertOk()
            ->assertSee('Document changes needed');

        $this->actingAs($admin)
            ->delete(route('admin.operations.documents.destroy', [$offering, $document]))
            ->assertRedirect(route('admin.operations.show', $offering));

        Storage::disk('local')->assertMissing($document->path);
        $this->assertDatabaseMissing('asset_documents', [
            'id' => $document->id,
        ]);
    }

    private function settledOffering(): Offering
    {
        DB::table('properties_status')->insertOrIgnore([
            ['id' => '1', 'status' => 'Active', 'description' => 'Test active status'],
        ]);

        $property = Property::factory()->create(['name' => 'Operations Asset']);

        return Offering::create([
            'property_id' => $property->id,
            'name' => 'Operations test offering',
            'status' => 'SETTLED',
            'target_raise' => '1000.00',
            'target_raise_cents' => 100000,
            'hard_cap_cents' => 100000,
            'org_reserve_pct' => '10.00',
            'min_local_capital_pct' => 0,
            'min_local_investor_count' => 1,
            'require_local_threshold_for_settlement' => true,
            'campaign_end' => now()->subDay(),
        ]);
    }
}
