<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hotel_operations_checklists') && ! Schema::hasTable('asset_operations_checklists')) {
            Schema::rename('hotel_operations_checklists', 'asset_operations_checklists');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('asset_operations_checklists') && ! Schema::hasTable('hotel_operations_checklists')) {
            Schema::rename('asset_operations_checklists', 'hotel_operations_checklists');
        }
    }
};
