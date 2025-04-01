<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('beds')->default(0);
            $table->integer('baths')->default(0);
            $table->integer('size')->default(0);
            $table->string('type')->default('unknown');
            $table->string('ber_rating')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('sale_type')->nullable();
            $table->decimal('stamp_duty', 10, 2)->default(0);
            $table->json('features')->nullable();
            $table->string('auctioneer_name')->nullable();
            $table->decimal('latitude', 10, 8)->default(0);
            $table->decimal('longitude', 11, 8)->default(0);
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('properties');
    }
}
