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
            $t->enum('status', ['DRAFT', 'OPEN', 'CLOSED', 'SETTLING', 'SETTLED', 'FAILED', 'REFUND_PENDING', 'REFUNDED', 'CANCELLED'])->default('DRAFT');
            $t->decimal('target_raise', 18, 2);
            $t->unsignedBigInteger('target_raise_cents')->nullable();
            $t->unsignedBigInteger('hard_cap_cents')->nullable();
            $t->enum('overfunding_policy', ['pro_rata_by_priority_bucket'])->default('pro_rata_by_priority_bucket');
            $t->decimal('org_reserve_pct', 6, 2)->default(10.00);
            $t->unsignedInteger('min_local_capital_pct')->default(25);
            $t->unsignedInteger('min_local_investor_count')->default(3);
            $t->boolean('require_local_threshold_for_settlement')->default(true);
            $t->unsignedInteger('max_local_voting_equity_bp')->default(1500);
            $t->enum('local_area_type', ['eircode_routing_key', 'eircode_list', 'polygon', 'admin_defined'])->default('admin_defined');
            $t->json('local_area_config')->nullable();
            $t->timestamp('local_eligibility_locked_at')->nullable();
            $t->enum('failure_reason', ['under_target', 'below_local_capital_floor', 'below_local_investor_floor', 'cancelled_by_admin', 'settlement_error'])->nullable();
            $t->text('failure_notes')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->unsignedInteger('default_loan_interest_rate_bp')->default(600);
            $t->unsignedInteger('default_loan_term_months')->default(36);
            $t->enum('default_loan_repayment_frequency', ['monthly', 'quarterly', 'bullet'])->default('monthly');
            $t->enum('default_loan_ranking', ['senior_unsecured', 'senior_secured', 'subordinated'])->default('senior_unsecured');
            $t->boolean('org_reserved_matter_veto')->default(true);
            $t->boolean('treasury_votes')->default(false);
            $t->boolean('lender_ordinary_votes')->default(false);
            $t->boolean('delegated_voting_enabled')->default(false);
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
