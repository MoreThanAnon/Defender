<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//The `offerings` table is used to represent **investment offerings** for properties on your platform.  
//**Business logic and purpose:**
//**Summary:**  
//This table is central to your investment logic, linking properties to fundraising rounds,
//  tracking their status, and storing key parameters for how investments and equity are allocated.
//More human description: It tracks the status of the campagn to buy the property
return new class extends Migration {
  public function up(): void {
    Schema::create('offerings', function (Blueprint $t) {
      $t->id();
      $t->foreignId('property_id')->constrained();
      $t->string('name');
      $t->enum('status', ['1','OPEN','CLOSED','SETTLED','CANCELLED'])->default('DRAFT');
      $t->decimal('target_raise', 18, 2);
      $t->decimal('org_reserve_pct', 6, 2)->default(10.00);  // your 10%
      $t->decimal('min_investment', 18, 2)->default(100.00);
      $t->timestamps();
      $t->index(['property_id','status']);
    });
  }
  public function down(): void { Schema::dropIfExists('offerings'); }
};