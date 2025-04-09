<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEircodeAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eircode_addresses', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('eircode', 10)->unique(); // Eircode (unique, max 10 characters)
            $table->string('address_line_1'); // Address Line 1
            $table->string('address_line_2')->nullable(); // Address Line 2 (optional)
            $table->string('town_city'); // Town or City
            $table->string('county'); // County
            $table->decimal('latitude', 10, 8)->nullable(); // Latitude (optional)
            $table->decimal('longitude', 11, 8)->nullable(); // Longitude (optional)
            $table->string('alias')->nullable(); // Alias information (optional)
            $table->string('boundary_data')->nullable(); // Boundary data (optional)
            $table->string('building_info')->nullable(); // Building information (optional)
            $table->timestamps(); // Created and updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('eircode_addresses');
    }
}
