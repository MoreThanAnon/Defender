<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('equity_classes')) {
            return;
        }

        if (! Schema::hasColumn('equity_classes', 'property_id')) {
            Schema::table('equity_classes', function (Blueprint $table) {
                $table->unsignedBigInteger('property_id')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn('equity_classes', 'hotel_id')) {
            DB::table('equity_classes')
                ->whereNull('property_id')
                ->update(['property_id' => DB::raw('hotel_id')]);
        }

        try {
            Schema::table('equity_classes', function (Blueprint $table) {
                $table->unique(['property_id', 'code'], 'equity_classes_property_code_unique');
            });
        } catch (\Throwable) {
            // Existing installs may already have an equivalent index.
        }
    }

    public function down(): void
    {
        // Compatibility repair migration; leave existing data in place on rollback.
    }
};
