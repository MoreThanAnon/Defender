<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equity_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained();
            $t->string('code', 32);
            $t->string('name');
            $t->json('voting_rights')->nullable();
            $t->json('transfer_restrictions')->nullable();
            $t->timestamps();
            $t->unique(['property_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equity_classes');
    }
};
