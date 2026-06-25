<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('decidim_governance_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('decidim_assembly_id')->nullable();
            $table->string('decidim_assembly_slug')->nullable();
            $table->string('decidim_assembly_url')->nullable();
            $table->string('decidim_debates_component_id')->nullable();
            $table->string('decidim_proposals_component_id')->nullable();
            $table->string('decidim_meetings_component_id')->nullable();
            $table->string('decidim_voting_component_id')->nullable();
            $table->string('decidim_taxonomy_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('decidim_governance_spaces'); }
};
