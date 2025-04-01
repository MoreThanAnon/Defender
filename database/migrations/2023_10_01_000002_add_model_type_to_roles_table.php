<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModelTypeToRolesTable extends Migration // Ensure this matches the file name
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('model_type', 255)->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('model_type');
        });
    }
}
