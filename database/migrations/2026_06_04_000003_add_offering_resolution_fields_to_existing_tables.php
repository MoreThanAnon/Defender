<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE offerings MODIFY status ENUM('DRAFT','OPEN','CLOSED','SETTLING','SETTLED','FAILED','REFUND_PENDING','REFUNDED','CANCELLED') DEFAULT 'DRAFT'");
            DB::statement("ALTER TABLE loan_notes MODIFY status ENUM('active','repaying','repaid','defaulted','written_off') DEFAULT 'active'");
        }

        Schema::table('offerings', function (Blueprint $table) {
            if (! Schema::hasColumn('offerings', 'target_raise_cents')) $table->unsignedBigInteger('target_raise_cents')->nullable()->after('target_raise');
            if (! Schema::hasColumn('offerings', 'hard_cap_cents')) $table->unsignedBigInteger('hard_cap_cents')->nullable()->after('target_raise_cents');
            if (! Schema::hasColumn('offerings', 'overfunding_policy')) $table->enum('overfunding_policy', ['pro_rata_by_priority_bucket'])->default('pro_rata_by_priority_bucket')->after('hard_cap_cents');
            if (! Schema::hasColumn('offerings', 'min_local_capital_pct')) $table->unsignedInteger('min_local_capital_pct')->default(25)->after('org_reserve_pct');
            if (! Schema::hasColumn('offerings', 'min_local_investor_count')) $table->unsignedInteger('min_local_investor_count')->default(3)->after('min_local_capital_pct');
            if (! Schema::hasColumn('offerings', 'require_local_threshold_for_settlement')) $table->boolean('require_local_threshold_for_settlement')->default(true)->after('min_local_investor_count');
            if (! Schema::hasColumn('offerings', 'max_local_voting_equity_bp')) $table->unsignedInteger('max_local_voting_equity_bp')->default(1500)->after('require_local_threshold_for_settlement');
            if (! Schema::hasColumn('offerings', 'local_area_type')) $table->enum('local_area_type', ['eircode_routing_key', 'eircode_list', 'polygon', 'admin_defined'])->default('admin_defined')->after('max_local_voting_equity_bp');
            if (! Schema::hasColumn('offerings', 'local_area_config')) $table->json('local_area_config')->nullable()->after('local_area_type');
            if (! Schema::hasColumn('offerings', 'local_eligibility_locked_at')) $table->timestamp('local_eligibility_locked_at')->nullable()->after('local_area_config');
            if (! Schema::hasColumn('offerings', 'failure_reason')) $table->enum('failure_reason', ['under_target', 'below_local_capital_floor', 'below_local_investor_floor', 'cancelled_by_admin', 'settlement_error'])->nullable()->after('local_eligibility_locked_at');
            if (! Schema::hasColumn('offerings', 'failure_notes')) $table->text('failure_notes')->nullable()->after('failure_reason');
            if (! Schema::hasColumn('offerings', 'resolved_at')) $table->timestamp('resolved_at')->nullable()->after('failure_notes');
            if (! Schema::hasColumn('offerings', 'default_loan_interest_rate_bp')) $table->unsignedInteger('default_loan_interest_rate_bp')->default(600)->after('resolved_at');
            if (! Schema::hasColumn('offerings', 'default_loan_term_months')) $table->unsignedInteger('default_loan_term_months')->default(36)->after('default_loan_interest_rate_bp');
            if (! Schema::hasColumn('offerings', 'default_loan_repayment_frequency')) $table->enum('default_loan_repayment_frequency', ['monthly', 'quarterly', 'bullet'])->default('monthly')->after('default_loan_term_months');
            if (! Schema::hasColumn('offerings', 'default_loan_ranking')) $table->enum('default_loan_ranking', ['senior_unsecured', 'senior_secured', 'subordinated'])->default('senior_unsecured')->after('default_loan_repayment_frequency');
            if (! Schema::hasColumn('offerings', 'org_reserved_matter_veto')) $table->boolean('org_reserved_matter_veto')->default(true)->after('default_loan_ranking');
            if (! Schema::hasColumn('offerings', 'treasury_votes')) $table->boolean('treasury_votes')->default(false)->after('org_reserved_matter_veto');
            if (! Schema::hasColumn('offerings', 'lender_ordinary_votes')) $table->boolean('lender_ordinary_votes')->default(false)->after('treasury_votes');
            if (! Schema::hasColumn('offerings', 'delegated_voting_enabled')) $table->boolean('delegated_voting_enabled')->default(false)->after('lender_ordinary_votes');
        });

        if (! Schema::hasColumn('bids', 'original_amount_cents')) {
            Schema::table('bids', fn (Blueprint $table) => $table->unsignedBigInteger('original_amount_cents')->nullable()->after('amount'));
        }
        if (! Schema::hasColumn('bids', 'accepted_amount_cents')) {
            Schema::table('bids', fn (Blueprint $table) => $table->unsignedBigInteger('accepted_amount_cents')->default(0)->after('original_amount_cents'));
        }
        if (! Schema::hasColumn('bids', 'refunded_amount_cents')) {
            Schema::table('bids', fn (Blueprint $table) => $table->unsignedBigInteger('refunded_amount_cents')->default(0)->after('accepted_amount_cents'));
        }
        if (! Schema::hasColumn('bids', 'is_local')) {
            Schema::table('bids', fn (Blueprint $table) => $table->boolean('is_local')->default(false)->after('refunded_amount_cents'));
        }
        if (! Schema::hasColumn('bids', 'is_local_at_bid_time')) {
            Schema::table('bids', fn (Blueprint $table) => $table->boolean('is_local_at_bid_time')->default(false)->after('is_local'));
        }
        if (! Schema::hasColumn('bids', 'local_eligibility_reason')) {
            Schema::table('bids', fn (Blueprint $table) => $table->json('local_eligibility_reason')->nullable()->after('is_local_at_bid_time'));
        }
        if (! Schema::hasColumn('bids', 'local_area_version')) {
            Schema::table('bids', fn (Blueprint $table) => $table->string('local_area_version')->nullable()->after('local_eligibility_reason'));
        }
        if (! Schema::hasColumn('bids', 'allocation_status')) {
            Schema::table('bids', fn (Blueprint $table) => $table->enum('allocation_status', ['pending', 'accepted_full', 'accepted_partial', 'rejected_excess', 'failed_campaign', 'refund_pending', 'refunded'])->default('pending')->after('status'));
        }

        Schema::table('equity_positions', function (Blueprint $table) {
            if (! Schema::hasColumn('equity_positions', 'position_type')) $table->enum('position_type', ['ORG_RESERVE', 'LOCAL_INVESTOR', 'TREASURY'])->default('LOCAL_INVESTOR')->after('source');
            if (! Schema::hasColumn('equity_positions', 'voting_rights')) $table->boolean('voting_rights')->default(true)->after('position_type');
            if (! Schema::hasColumn('equity_positions', 'can_vote')) $table->boolean('can_vote')->default(true)->after('voting_rights');
            if (! Schema::hasColumn('equity_positions', 'voting_bp')) $table->unsignedInteger('voting_bp')->default(0)->after('can_vote');
            if (! Schema::hasColumn('equity_positions', 'economic_bp')) $table->unsignedInteger('economic_bp')->default(0)->after('voting_bp');
        });

        Schema::table('loan_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('loan_notes', 'investor_id')) $table->foreignId('investor_id')->nullable()->constrained('users')->after('lender_user_id');
            if (! Schema::hasColumn('loan_notes', 'principal_cents')) $table->unsignedBigInteger('principal_cents')->nullable()->after('principal');
            if (! Schema::hasColumn('loan_notes', 'repayment_frequency')) $table->enum('repayment_frequency', ['monthly', 'quarterly', 'bullet'])->default('monthly')->after('term_months');
            if (! Schema::hasColumn('loan_notes', 'maturity_date')) $table->date('maturity_date')->nullable()->after('repayment_frequency');
            if (! Schema::hasColumn('loan_notes', 'ranking')) $table->enum('ranking', ['senior_unsecured', 'senior_secured', 'subordinated'])->default('senior_unsecured')->after('status');
            if (! Schema::hasColumn('loan_notes', 'early_repayment_allowed')) $table->boolean('early_repayment_allowed')->default(true)->after('ranking');
            if (! Schema::hasColumn('loan_notes', 'early_repayment_fee_bp')) $table->unsignedInteger('early_repayment_fee_bp')->nullable()->default(0)->after('early_repayment_allowed');
            if (! Schema::hasColumn('loan_notes', 'default_interest_rate_bp')) $table->unsignedInteger('default_interest_rate_bp')->nullable()->after('early_repayment_fee_bp');
            if (! Schema::hasColumn('loan_notes', 'has_protective_votes')) $table->boolean('has_protective_votes')->default(true)->after('default_interest_rate_bp');
            if (! Schema::hasColumn('loan_notes', 'voting_scope')) $table->enum('voting_scope', ['debt_protective_only'])->default('debt_protective_only')->after('has_protective_votes');
        });
    }

    public function down(): void
    {
        // Additive compatibility migration; keep data/columns on rollback.
    }
};
