<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hotel_operations_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->decimal('bank_balance', 15, 2)->nullable();
            $table->json('bills_due')->nullable();
            $table->json('maintenance_tasks')->nullable();
            $table->json('meeting_status')->nullable();
            $table->json('proposal_status')->nullable();
            $table->json('legal_review')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_operations_checklists');
    }
};
