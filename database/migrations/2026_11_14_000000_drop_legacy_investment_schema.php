<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retires the pre-November-2026 investment platform schema: offerings, bids,
 * equity, loan notes, deposits/withdrawals, Stripe customer columns, the unused
 * role/permission tables and the reporting views built on them.
 *
 * On a fresh database this does nothing. On a database that ran the old
 * migrations it brings the schema to the same shape as a fresh install.
 *
 * NOT reversible. Take a database dump first (one from 14 Nov 2026 is in
 * storage/app/backups on the original development machine).
 */
return new class extends Migration
{
    private const VIEWS = [
        'business_cashflow_monthly_overview', 'business_investor_overview', 'business_loan_note_overview',
        'business_property_funding_overview', 'business_property_ownership_overview', 'business_refund_overview',
    ];

    private const TABLES = [
        // investment / fundraising
        'funding_events', 'refund_records', 'loan_notes', 'equity_positions', 'equity_classes', 'investments', 'bids', 'offerings',
        // property listings and their governance sync
        'asset_activity_logs', 'asset_documents', 'asset_operations_checklists', 'hotel_operations_checklists',
        'decidim_governance_spaces', 'decidim_governance_syncs', 'votes', 'proposals', 'updates', 'images',
        'properties', 'properties_status',
        // custody: balances, card deposits, withdrawals, Stripe
        'deposits', 'withdrawal', 'charge_logs', 'subscription_items', 'subscriptions',
        // never-used role / permission / address / token tables
        'model_has_permissions', 'role_has_permissions', 'model_has_roles', 'permission_role', 'permissions',
        'role_user', 'roles', 'address', 'eircode_addresses', 'password_resets', 'personal_access_tokens',
    ];

    private const USER_COLUMNS = [
        'balance', 'funds', 'stripe_customer_id', 'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at',
        'eircode_region', 'display_name',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach (self::VIEWS as $view) {
                DB::statement("DROP VIEW IF EXISTS `$view`");
            }
        }

        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'stripe_id')) {
            try {
                Schema::table('users', fn (Blueprint $table) => $table->dropIndex(['stripe_id']));
            } catch (Throwable) {
                // index already gone
            }
        }

        $drop = array_values(array_filter(self::USER_COLUMNS, fn ($column) => Schema::hasColumn('users', $column)));
        if ($drop) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($drop));
        }

        // Old platform roles were guest / user / moderator / admin. Only "admin" keeps any meaning.
        DB::table('users')->where('role', '!=', 'admin')->update(['role' => 'user']);
    }

    public function down(): void
    {
        // Intentionally empty: restore from a database dump.
    }
};
