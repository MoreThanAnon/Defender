<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('bids')) { // Check if the table already exists
            Schema::create('bids', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id'); // Foreign key for the property
                $table->unsignedBigInteger('user_id'); // Foreign key for the user
                $table->decimal('amount', 10, 2); // Bid amount
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending'); // Status of the bid
                $table->timestamp('bid_time')->useCurrent(); // Time when the bid was made
                $table->timestamps(); // This creates 'created_at' and 'updated_at' columns
                // Foreign key constraints
                $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
