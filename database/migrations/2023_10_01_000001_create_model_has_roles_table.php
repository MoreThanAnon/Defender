<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelHasRolesTable extends Migration // Ensure this matches the file name
{
    public function up()
    {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->morphs('model'); // Creates 'model_id' and 'model_type' columns
            $table->primary(['role_id', 'model_id', 'model_type']);

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('model_has_roles');
    }
}
