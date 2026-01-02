<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('equity_positions', function (Blueprint $t) {
            $t->id(); // Primary key
            $t->foreignId('offering_id')->constrained();      // Link to the offering (campaign)
            $t->foreignId('equity_class')->constrained();     // Link to the equity class (e.g., Ordinary, Preferred)
            $t->foreignId('holder_user_id')->nullable()->constrained('users'); // User who holds this equity (nullable for treasury/org)
            $t->enum('holder_type', ['USER','ORG','TREASURY']); // Who holds it: user, organization, or treasury
            $t->decimal('pct_ownership', 9, 4);               // Percentage of ownership (e.g., 12.3456%)
            $t->enum('source', ['ORG_RESERVE','INVESTOR_POOL','SECONDARY']); // How this equity was acquired
            $t->timestamp('locked_until')->nullable();         // When this equity is locked until (if any)
            $t->timestamps();                                  // created_at and updated_at
            $t->index(['offering_id','equity_class_id']);      // Index for fast lookup by offering and equity class
        });
    }
    public function down(): void { Schema::dropIfExists('equity_positions'); }
};