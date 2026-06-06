<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('bids')) {
            return;
        }

        if (! Schema::hasColumn('bids', 'offering_id')) {
            Schema::table('bids', function (Blueprint $table) {
                $table->unsignedBigInteger('offering_id')->nullable()->after('property_id');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bids MODIFY status ENUM('pending','rejected','PLEDGED','ACCEPTED') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // Compatibility repair migration; leave existing data in place on rollback.
    }
};
