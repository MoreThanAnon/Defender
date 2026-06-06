<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('equity_classes') || ! Schema::hasColumn('equity_classes', 'hotel_id')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE equity_classes MODIFY hotel_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Compatibility repair migration; leave existing data in place on rollback.
    }
};
