<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_documents', function (Blueprint $table) {
            $table->boolean('visible_to_investors')->default(false)->after('review_status');
            $table->timestamp('published_at')->nullable()->after('visible_to_investors');
            $table->text('publication_notes')->nullable()->after('published_at');

            $table->index(['offering_id', 'visible_to_investors', 'review_status'], 'asset_documents_investor_visible_index');
        });
    }

    public function down(): void
    {
        Schema::table('asset_documents', function (Blueprint $table) {
            $table->dropIndex('asset_documents_investor_visible_index');
            $table->dropColumn(['visible_to_investors', 'published_at', 'publication_notes']);
        });
    }
};
