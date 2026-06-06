<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('equity_positions')) {
            return;
        }

        if (! Schema::hasColumn('equity_positions', 'offering_id')) {
            Schema::table('equity_positions', function (Blueprint $table) {
                $table->unsignedBigInteger('offering_id')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('equity_positions', 'equity_class_id')) {
            Schema::table('equity_positions', function (Blueprint $table) {
                $table->unsignedBigInteger('equity_class_id')->nullable()->after('offering_id');
            });
        }

        Schema::table('equity_positions', function (Blueprint $table) {
            $table->index(['offering_id', 'equity_class_id'], 'equity_positions_offering_class_index');
        });
    }

    public function down(): void
    {
        // Compatibility repair migration; leave existing data in place on rollback.
    }
};
