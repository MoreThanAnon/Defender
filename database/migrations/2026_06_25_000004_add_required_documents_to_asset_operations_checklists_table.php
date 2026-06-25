<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_operations_checklists', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_operations_checklists', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('maintenance_tasks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_operations_checklists', function (Blueprint $table) {
            if (Schema::hasColumn('asset_operations_checklists', 'required_documents')) {
                $table->dropColumn('required_documents');
            }
        });
    }
};
