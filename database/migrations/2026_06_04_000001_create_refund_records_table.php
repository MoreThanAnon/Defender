<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refund_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained();
            $table->foreignId('bid_id')->nullable()->constrained('bids')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['offering_id', 'bid_id']);
            $table->index(['offering_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_records');
    }
};
