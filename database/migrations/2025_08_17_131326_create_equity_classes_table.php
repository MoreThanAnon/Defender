<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('equity_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('hotel_id')->constrained();         // hotels.id
            $t->string('code', 32);                            // e.g., ORD-A
            $t->string('name');
            $t->json('voting_rights')->nullable();
            $t->json('transfer_restrictions')->nullable();
            $t->timestamps();
            $t->unique(['hotel_id','code']);
        });
    }
    public function down(): void { Schema::dropIfExists('equity_classes'); }
};