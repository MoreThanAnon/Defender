<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('decidim_governance_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained();
            $table->foreignId('investor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decidim_user_id')->nullable();
            $table->enum('role', ['local_equity_voter', 'org_steward', 'non_voting_lender', 'admin']);
            $table->boolean('can_vote')->default(false);
            $table->unsignedInteger('voting_bp')->default(0);
            $table->string('voting_scope')->default('none');
            $table->timestamp('synced_at');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['offering_id', 'investor_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decidim_governance_syncs');
    }
};
