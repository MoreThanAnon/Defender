<?php

use App\Models\{Hotel, Offering, EquityClass, User, Bid, Property};
use App\Actions\Offerings\SettleOffering;
use App\Services\EquityAllocationService;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test SettleOffering ===\n";

// Seed test data
$property = Property::firstOrCreate(['id' => 1]);
$offering = Offering::create([
    'hotel_id' => $property->id,
    'name' => 'Test CLI Offering '.now()->format('Ymd_His'),
    'status' => 'CLOSED',
    'target_raise' => 300000.00,
    'org_reserve_pct' => 10.00,
    'min_investment' => 100.00,
    'price_per_percent' => null,
]);
$equityClass = EquityClass::firstOrCreate(
    ['hotel_id' => $property->id, 'code' => 'ORD-A'],
    ['name' => 'Ordinary A']
);

$suffix = (string) now()->timestamp;
$u1 = User::create(['name'=>'Local One', 'email'=>"local1+$suffix@example.com",   'password'=>Hash::make('password')]);
$u2 = User::create(['name'=>'Local Two', 'email'=>"local2+$suffix@example.com",   'password'=>Hash::make('password')]);
$u3 = User::create(['name'=>'Non Local', 'email'=>"nonlocal+$suffix@example.com", 'password'=>Hash::make('password')]);

Bid::create(['offering_id'=>$offering->id,'user_id'=>$u1->id,'amount'=>50000.00,'is_local'=>true,  'status'=>'PLEDGED']);
Bid::create(['offering_id'=>$offering->id,'user_id'=>$u2->id,'amount'=>25000.00,'is_local'=>true,  'status'=>'PLEDGED']);
Bid::create(['offering_id'=>$offering->id,'user_id'=>$u3->id,'amount'=>100000.00,'is_local'=>false,'status'=>'PLEDGED']);

// Run settlement
$service = new EquityAllocationService();
$settler = new SettleOffering($service);
$settler->handle($offering, $equityClass->id, null);

echo "Settlement complete.\n";

// Output results
$offering->refresh();
$positions = $offering->equityPositions()->with('holder:id,name,email')->get();
$loans = $offering->loanNotes()->with('lender:id,name,email')->get();

echo "Equity Positions:\n";
foreach ($positions as $p) {
    echo "- {$p->holder_type}: {$p->holder?->name} ({$p->holder?->email}) owns {$p->pct_ownership}% [{$p->source}]\n";
}
echo "Loan Notes:\n";
foreach ($loans as $ln) {
    echo "- {$ln->lender?->name} ({$ln->lender?->email}) principal: {$ln->principal}, rate: {$ln->interest_rate_bp}bp, term: {$ln->term_months}m\n";
}