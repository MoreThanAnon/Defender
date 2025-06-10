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
        Schema::create('updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('title');
            $table->MEDIUMTEXT('content');
            $table->string('description')->nullable();
            $table->string('author')->nullable();
            $table->string('image')->nullable();
            $table->string('file')->nullable();//location to a file to download, be carful with this, no allowing uploading of exes or anythign
            $table->enum('file_type', ['pdf', 'doc', 'docx', 'jpg', 'png', 'xlsx'])->nullable();

            $table->timestamps();
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('updates');
    }
};
