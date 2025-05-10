<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargeLogsTable extends Migration
{
    public function up()
    {
        Schema::create('charge_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request',500);
            $table->longText('response');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('charge_logs');
    }
}
