<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bids')) {
            Schema::create('bids', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('offering_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->unsignedBigInteger('original_amount_cents')->nullable();
                $table->unsignedBigInteger('accepted_amount_cents')->default(0);
                $table->unsignedBigInteger('refunded_amount_cents')->default(0);
                $table->boolean('is_local')->default(false);
                $table->boolean('is_local_at_bid_time')->default(false);
                $table->json('local_eligibility_reason')->nullable();
                $table->string('local_area_version')->nullable();
                $table->enum('status', ['pending', 'accepted', 'rejected', 'PLEDGED', 'ACCEPTED'])->default('pending');
                $table->enum('allocation_status', ['pending', 'accepted_full', 'accepted_partial', 'rejected_excess', 'failed_campaign', 'refund_pending', 'refunded'])->default('pending');
                $table->timestamp('bid_time')->useCurrent();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
