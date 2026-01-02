<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//https://www.investopedia.com/terms/l/loan-note.asp
return new class extends Migration {
    public function up(): void {
        Schema::create('loan_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offering_id')->constrained();      // offerings.id
            $t->foreignId('lender_user_id')->constrained('users');
            $t->decimal('principal', 18, 2);
            $t->decimal('principal_paid', 18, 2)->default(0); // <-- Track amount paid off
            $t->unsignedInteger('interest_rate_bp');          // e.g., 600 = 6.00%
            $t->unsignedInteger('term_months');               // e.g., 36
            $t->enum('status', ['ACTIVE','REPAID','DEFAULTED'])->default('ACTIVE');
            $t->timestamp('last_payment_at')->nullable();      // <-- Track last payment date
            $t->timestamps();
            $t->index(['offering_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('loan_notes'); }
};