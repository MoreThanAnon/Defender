<?php

namespace Database\Seeders;

use App\Models\User;
use BusinessCore\Models\Asset;
use BusinessCore\Models\Business;
use BusinessCore\Models\Project;
use BusinessCore\Models\Supplier;
use BusinessCore\Models\Task;
use BusinessCore\Services\BusinessLifecycle;
use BusinessCore\Services\StatementImporter;
use Illuminate\Database\Seeder;

/**
 * A practice business kept in storage, with a fake bank statement, so the
 * screens have something to show. Local environment only. Safe to run twice.
 */
class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        if (Business::where('name', 'Demo: Project 027 Ltd')->exists()) {
            return;
        }

        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

        $business = app(BusinessLifecycle::class)->create([
            'name' => 'Demo: Project 027 Ltd',
            'type' => 'business',
            'bank_url' => 'https://example.com/your-bank',
            'notes' => 'Practice data only.',
        ], $admin);

        $site = Asset::create(['business_id' => $business->id, 'name' => 'Main site', 'kind' => 'land', 'location' => 'Co. Cork']);
        $repairs = Project::create([
            'business_id' => $business->id, 'asset_id' => $site->id, 'name' => 'Repairs 2026',
            'description' => 'Pump, fencing and general repairs.', 'status' => 'active', 'budget_amount' => 3500,
        ]);
        Supplier::create([
            'business_id' => $business->id, 'name' => 'Lee Valley Pumps', 'email' => 'accounts@leevalleypumps.example',
            'payment_details' => "Lee Valley Pumps Ltd\nIBAN: IE00 DEMO 0000 0000 0000 00 (fake)",
        ]);
        Task::create([
            'business_id' => $business->id, 'project_id' => $repairs->id, 'title' => 'Site inspection',
            'assigned_to' => $admin->id, 'due_date' => now()->subDays(3)->toDateString(), 'status' => 'todo',
        ]);

        app(StatementImporter::class)->import(
            $business,
            base_path('tests/Fixtures/statement-october.csv'),
            'statement-october.csv (demo)',
            $admin,
        );
    }
}
