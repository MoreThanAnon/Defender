<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('equity_positions')) {
            return;
        }

        Schema::create('equity_positions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offering_id')->constrained();
            $t->foreignId('equity_class_id')->constrained('equity_classes');
            $t->foreignId('holder_user_id')->nullable()->constrained('users');
            $t->enum('holder_type', ['USER', 'ORG', 'TREASURY']);
            $t->decimal('pct_ownership', 9, 4);
            $t->enum('source', ['ORG_RESERVE', 'INVESTOR_POOL', 'SECONDARY']);
            $t->enum('position_type', ['ORG_RESERVE', 'LOCAL_INVESTOR', 'TREASURY'])->default('LOCAL_INVESTOR');
            $t->boolean('voting_rights')->default(true);
            $t->boolean('can_vote')->default(true);
            $t->unsignedInteger('voting_bp')->default(0);
            $t->unsignedInteger('economic_bp')->default(0);
            $t->timestamp('locked_until')->nullable();
            $t->timestamps();
            $t->index(['offering_id', 'equity_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equity_positions');
    }
};
