<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//https://www.investopedia.com/terms/l/loan-note.asp
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('loan_notes')) {
            return;
        }

        Schema::create('loan_notes', function (Blueprint $t) {
            $t->id();
            if (Schema::hasTable('offerings')) {
                $t->foreignId('offering_id')->constrained();
            } else {
                $t->unsignedBigInteger('offering_id');
            }
            $t->foreignId('lender_user_id')->constrained('users');
            $t->foreignId('investor_id')->nullable()->constrained('users');
            $t->decimal('principal', 18, 2);
            $t->unsignedBigInteger('principal_cents')->nullable();
            $t->decimal('principal_paid', 18, 2)->default(0); // <-- Track amount paid off
            $t->unsignedInteger('interest_rate_bp');          // e.g., 600 = 6.00%
            $t->unsignedInteger('term_months');               // e.g., 36
            $t->enum('repayment_frequency', ['monthly', 'quarterly', 'bullet'])->default('monthly');
            $t->date('maturity_date')->nullable();
            $t->enum('status', ['active', 'repaying', 'repaid', 'defaulted', 'written_off'])->default('active');
            $t->enum('ranking', ['senior_unsecured', 'senior_secured', 'subordinated'])->default('senior_unsecured');
            $t->boolean('early_repayment_allowed')->default(true);
            $t->unsignedInteger('early_repayment_fee_bp')->nullable()->default(0);
            $t->unsignedInteger('default_interest_rate_bp')->nullable();
            $t->boolean('has_protective_votes')->default(true);
            $t->enum('voting_scope', ['debt_protective_only'])->default('debt_protective_only');
            $t->timestamp('last_payment_at')->nullable();      // <-- Track last payment date
            $t->timestamps();
            $t->index(['offering_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('loan_notes'); }
};
