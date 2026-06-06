<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('equity_positions') || ! Schema::hasColumn('equity_positions', 'equity_class')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE equity_positions MODIFY equity_class BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Compatibility repair migration; leave existing data in place on rollback.
    }
};
