<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_key');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('review_status')->default('needs_review');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['offering_id', 'document_key']);
            $table->index(['review_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_documents');
    }
};
