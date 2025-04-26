<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->integer('beds');
            $table->string('daft_link')->nullable(); // Add the 'daft_link' column
            $table->integer('baths');
            $table->decimal('size', 10, 2);
            $table->string('type', 50);
            $table->string('ber_rating', 10)->nullable();
            $table->string('address');
            $table->string('eircode', 10)->nullable();
            $table->text('description')->nullable();
            $table->string('sale_type', 50)->nullable();
            $table->decimal('stamp_duty', 15, 2)->nullable();
            $table->json('features')->nullable();
            $table->string('auctioneer_name', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('properties');
    }
}
