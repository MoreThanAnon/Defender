<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('funding_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('event_type');
            $table->decimal('amount', 18, 2)->nullable();
            $table->decimal('equity_pct', 6, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['offering_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_events');
    }
};
