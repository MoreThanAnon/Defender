<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offerings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained();
            $t->string('name');
            $t->enum('status', ['DRAFT', 'OPEN', 'CLOSED', 'SETTLED', 'CANCELLED'])->default('DRAFT');
            $t->decimal('target_raise', 18, 2);
            $t->decimal('org_reserve_pct', 6, 2)->default(10.00);
            $t->decimal('min_investment', 18, 2)->default(100.00);
            $t->timestamp('campaign_end')->nullable();
            $t->timestamps();
            $t->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offerings');
    }
};
