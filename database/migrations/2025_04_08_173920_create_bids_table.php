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
                $table->boolean('is_local')->default(false);
                $table->enum('status', ['pending', 'accepted', 'rejected', 'PLEDGED', 'ACCEPTED'])->default('pending');
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
