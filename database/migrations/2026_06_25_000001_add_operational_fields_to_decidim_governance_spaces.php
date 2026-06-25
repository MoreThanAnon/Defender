<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('decidim_governance_spaces', function (Blueprint $table) {
            if (! Schema::hasColumn('decidim_governance_spaces', 'retry_count')) {
                $table->unsignedInteger('retry_count')->default(0)->after('status');
            }
            if (! Schema::hasColumn('decidim_governance_spaces', 'last_attempted_at')) {
                $table->timestamp('last_attempted_at')->nullable()->after('retry_count');
            }
            if (! Schema::hasColumn('decidim_governance_spaces', 'last_succeeded_at')) {
                $table->timestamp('last_succeeded_at')->nullable()->after('last_attempted_at');
            }
            if (! Schema::hasColumn('decidim_governance_spaces', 'last_failed_at')) {
                $table->timestamp('last_failed_at')->nullable()->after('last_succeeded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('decidim_governance_spaces', function (Blueprint $table) {
            foreach (['last_failed_at', 'last_succeeded_at', 'last_attempted_at', 'retry_count'] as $column) {
                if (Schema::hasColumn('decidim_governance_spaces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
