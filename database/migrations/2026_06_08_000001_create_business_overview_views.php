<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private array $views = [
        'business_property_ownership_overview',
        'business_property_funding_overview',
        'business_investor_overview',
        'business_loan_note_overview',
        'business_refund_overview',
        'business_cashflow_monthly_overview',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->dropViews();

        DB::statement(<<<'SQL'
CREATE VIEW business_property_ownership_overview AS
SELECT
    p.id AS property_id,
    p.name AS property_name,
    p.address AS property_address,
    ps.status AS property_status,
    o.id AS offering_id,
    o.name AS offering_name,
    o.status AS offering_status,
    ec.id AS equity_class_id,
    ec.code AS equity_class_code,
    ec.name AS equity_class_name,
    ep.id AS equity_position_id,
    ep.holder_type,
    ep.holder_user_id,
    COALESCE(u.display_name, u.real_name, u.username) AS holder_name,
    u.email AS holder_email,
    ep.position_type,
    ep.source,
    ep.pct_ownership,
    ep.economic_bp,
    ROUND(ep.economic_bp / 100, 4) AS economic_pct,
    ep.voting_bp,
    ROUND(ep.voting_bp / 100, 4) AS voting_pct,
    ep.can_vote,
    ep.voting_rights,
    ep.locked_until,
    ep.created_at
FROM equity_positions ep
INNER JOIN offerings o ON o.id = ep.offering_id
INNER JOIN properties p ON p.id = o.property_id
LEFT JOIN properties_status ps ON ps.id = p.status
LEFT JOIN equity_classes ec ON ec.id = ep.equity_class_id
LEFT JOIN users u ON u.id = ep.holder_user_id
SQL);

        DB::statement(<<<'SQL'
CREATE VIEW business_property_funding_overview AS
SELECT
    p.id AS property_id,
    p.name AS property_name,
    p.price AS property_price,
    p.address AS property_address,
    ps.status AS property_status,
    o.id AS offering_id,
    o.name AS offering_name,
    o.status AS offering_status,
    o.target_raise,
    COALESCE(o.target_raise_cents, ROUND(o.target_raise * 100)) AS target_raise_cents,
    o.hard_cap_cents,
    o.org_reserve_pct,
    o.min_local_capital_pct,
    o.min_local_investor_count,
    o.campaign_end,
    o.resolved_at,
    COALESCE(bid_totals.bid_count, 0) AS bid_count,
    COALESCE(bid_totals.investor_count, 0) AS investor_count,
    COALESCE(bid_totals.local_investor_count, 0) AS local_investor_count,
    COALESCE(bid_totals.pledged_cents, 0) AS pledged_cents,
    ROUND(COALESCE(bid_totals.pledged_cents, 0) / 100, 2) AS pledged_amount,
    COALESCE(bid_totals.accepted_cents, 0) AS accepted_cents,
    ROUND(COALESCE(bid_totals.accepted_cents, 0) / 100, 2) AS accepted_amount,
    COALESCE(bid_totals.local_accepted_cents, 0) AS local_accepted_cents,
    ROUND(COALESCE(bid_totals.local_accepted_cents, 0) / 100, 2) AS local_accepted_amount,
    COALESCE(bid_totals.nonlocal_accepted_cents, 0) AS nonlocal_accepted_cents,
    ROUND(COALESCE(bid_totals.nonlocal_accepted_cents, 0) / 100, 2) AS nonlocal_accepted_amount,
    COALESCE(bid_totals.refunded_cents, 0) AS refunded_cents,
    ROUND(COALESCE(bid_totals.refunded_cents, 0) / 100, 2) AS refunded_amount,
    CASE
        WHEN COALESCE(o.target_raise_cents, ROUND(o.target_raise * 100)) > 0
            THEN ROUND((COALESCE(bid_totals.accepted_cents, 0) / COALESCE(o.target_raise_cents, ROUND(o.target_raise * 100))) * 100, 2)
        ELSE 0
    END AS funded_pct,
    CASE
        WHEN COALESCE(bid_totals.accepted_cents, 0) > 0
            THEN ROUND((COALESCE(bid_totals.local_accepted_cents, 0) / COALESCE(bid_totals.accepted_cents, 0)) * 100, 2)
        ELSE 0
    END AS local_capital_pct,
    COALESCE(equity_totals.investor_equity_bp, 0) AS investor_equity_bp,
    ROUND(COALESCE(equity_totals.investor_equity_bp, 0) / 100, 4) AS investor_equity_pct,
    COALESCE(equity_totals.org_equity_bp, 0) AS org_equity_bp,
    ROUND(COALESCE(equity_totals.org_equity_bp, 0) / 100, 4) AS org_equity_pct,
    COALESCE(loan_totals.loan_note_count, 0) AS loan_note_count,
    COALESCE(loan_totals.loan_principal_cents, 0) AS loan_principal_cents,
    ROUND(COALESCE(loan_totals.loan_principal_cents, 0) / 100, 2) AS loan_principal_amount
FROM properties p
LEFT JOIN properties_status ps ON ps.id = p.status
LEFT JOIN offerings o ON o.property_id = p.id
LEFT JOIN (
    SELECT
        offering_id,
        COUNT(*) AS bid_count,
        COUNT(DISTINCT user_id) AS investor_count,
        COUNT(DISTINCT CASE WHEN COALESCE(is_local_at_bid_time, is_local) = 1 THEN user_id END) AS local_investor_count,
        SUM(COALESCE(original_amount_cents, ROUND(amount * 100))) AS pledged_cents,
        SUM(COALESCE(accepted_amount_cents, 0)) AS accepted_cents,
        SUM(CASE WHEN COALESCE(is_local_at_bid_time, is_local) = 1 THEN COALESCE(accepted_amount_cents, 0) ELSE 0 END) AS local_accepted_cents,
        SUM(CASE WHEN COALESCE(is_local_at_bid_time, is_local) = 0 THEN COALESCE(accepted_amount_cents, 0) ELSE 0 END) AS nonlocal_accepted_cents,
        SUM(COALESCE(refunded_amount_cents, 0)) AS refunded_cents
    FROM bids
    WHERE offering_id IS NOT NULL
    GROUP BY offering_id
) bid_totals ON bid_totals.offering_id = o.id
LEFT JOIN (
    SELECT
        offering_id,
        SUM(CASE WHEN position_type = 'LOCAL_INVESTOR' THEN economic_bp ELSE 0 END) AS investor_equity_bp,
        SUM(CASE WHEN position_type = 'ORG_RESERVE' THEN economic_bp ELSE 0 END) AS org_equity_bp
    FROM equity_positions
    GROUP BY offering_id
) equity_totals ON equity_totals.offering_id = o.id
LEFT JOIN (
    SELECT
        offering_id,
        COUNT(*) AS loan_note_count,
        SUM(COALESCE(principal_cents, ROUND(principal * 100))) AS loan_principal_cents
    FROM loan_notes
    GROUP BY offering_id
) loan_totals ON loan_totals.offering_id = o.id
SQL);

        DB::statement(<<<'SQL'
CREATE VIEW business_investor_overview AS
SELECT
    u.id AS user_id,
    u.username,
    COALESCE(u.display_name, u.real_name, u.username) AS investor_name,
    u.email,
    u.role,
    u.eircode_region,
    u.balance,
    u.funds,
    COALESCE(deposit_totals.deposit_count, 0) AS deposit_count,
    COALESCE(deposit_totals.gross_deposits, 0) AS gross_deposits,
    COALESCE(deposit_totals.net_deposits, 0) AS net_deposits,
    COALESCE(withdrawal_totals.withdrawal_count, 0) AS withdrawal_count,
    COALESCE(withdrawal_totals.gross_withdrawals, 0) AS gross_withdrawals,
    COALESCE(withdrawal_totals.net_withdrawals, 0) AS net_withdrawals,
    COALESCE(bid_totals.bid_count, 0) AS bid_count,
    COALESCE(bid_totals.properties_bid_on, 0) AS properties_bid_on,
    ROUND(COALESCE(bid_totals.pledged_cents, 0) / 100, 2) AS pledged_amount,
    ROUND(COALESCE(bid_totals.accepted_cents, 0) / 100, 2) AS accepted_amount,
    ROUND(COALESCE(equity_totals.economic_bp, 0) / 100, 4) AS total_equity_pct_points,
    COALESCE(equity_totals.equity_positions_count, 0) AS equity_positions_count,
    COALESCE(loan_totals.loan_note_count, 0) AS loan_note_count,
    ROUND(COALESCE(loan_totals.loan_principal_cents, 0) / 100, 2) AS loan_principal_amount,
    ROUND(COALESCE(loan_totals.loan_outstanding_cents, 0) / 100, 2) AS loan_outstanding_amount,
    ROUND(COALESCE(refund_totals.refund_cents, 0) / 100, 2) AS refund_amount
FROM users u
LEFT JOIN (
    SELECT user_id, COUNT(*) AS deposit_count, SUM(gross_amount) AS gross_deposits, SUM(net_amount) AS net_deposits
    FROM deposits
    GROUP BY user_id
) deposit_totals ON deposit_totals.user_id = u.id
LEFT JOIN (
    SELECT user_id, COUNT(*) AS withdrawal_count, SUM(gross_amount) AS gross_withdrawals, SUM(net_amount) AS net_withdrawals
    FROM withdrawal
    GROUP BY user_id
) withdrawal_totals ON withdrawal_totals.user_id = u.id
LEFT JOIN (
    SELECT
        user_id,
        COUNT(*) AS bid_count,
        COUNT(DISTINCT property_id) AS properties_bid_on,
        SUM(COALESCE(original_amount_cents, ROUND(amount * 100))) AS pledged_cents,
        SUM(COALESCE(accepted_amount_cents, 0)) AS accepted_cents
    FROM bids
    GROUP BY user_id
) bid_totals ON bid_totals.user_id = u.id
LEFT JOIN (
    SELECT holder_user_id AS user_id, COUNT(*) AS equity_positions_count, SUM(economic_bp) AS economic_bp
    FROM equity_positions
    WHERE holder_user_id IS NOT NULL
    GROUP BY holder_user_id
) equity_totals ON equity_totals.user_id = u.id
LEFT JOIN (
    SELECT
        COALESCE(investor_id, lender_user_id) AS user_id,
        COUNT(*) AS loan_note_count,
        SUM(COALESCE(principal_cents, ROUND(principal * 100))) AS loan_principal_cents,
        SUM(GREATEST(COALESCE(principal_cents, ROUND(principal * 100)) - ROUND(COALESCE(principal_paid, 0) * 100), 0)) AS loan_outstanding_cents
    FROM loan_notes
    GROUP BY COALESCE(investor_id, lender_user_id)
) loan_totals ON loan_totals.user_id = u.id
LEFT JOIN (
    SELECT user_id, SUM(amount_cents) AS refund_cents
    FROM refund_records
    GROUP BY user_id
) refund_totals ON refund_totals.user_id = u.id
SQL);

        DB::statement(<<<'SQL'
CREATE VIEW business_loan_note_overview AS
SELECT
    ln.id AS loan_note_id,
    p.id AS property_id,
    p.name AS property_name,
    o.id AS offering_id,
    o.name AS offering_name,
    COALESCE(ln.investor_id, ln.lender_user_id) AS investor_user_id,
    COALESCE(u.display_name, u.real_name, u.username) AS investor_name,
    u.email AS investor_email,
    ln.status,
    ln.ranking,
    ln.repayment_frequency,
    ln.interest_rate_bp,
    ROUND(ln.interest_rate_bp / 100, 2) AS interest_rate_pct,
    ln.term_months,
    ln.maturity_date,
    COALESCE(ln.principal_cents, ROUND(ln.principal * 100)) AS principal_cents,
    ln.principal AS principal_amount,
    ln.principal_paid,
    ROUND(GREATEST(COALESCE(ln.principal_cents, ROUND(ln.principal * 100)) - ROUND(COALESCE(ln.principal_paid, 0) * 100), 0) / 100, 2) AS outstanding_amount,
    ln.last_payment_at,
    ln.created_at
FROM loan_notes ln
INNER JOIN offerings o ON o.id = ln.offering_id
INNER JOIN properties p ON p.id = o.property_id
LEFT JOIN users u ON u.id = COALESCE(ln.investor_id, ln.lender_user_id)
SQL);

        DB::statement(<<<'SQL'
CREATE VIEW business_refund_overview AS
SELECT
    rr.id AS refund_record_id,
    p.id AS property_id,
    p.name AS property_name,
    o.id AS offering_id,
    o.name AS offering_name,
    rr.bid_id,
    rr.user_id,
    COALESCE(u.display_name, u.real_name, u.username) AS user_name,
    u.email AS user_email,
    rr.status,
    rr.amount_cents,
    ROUND(rr.amount_cents / 100, 2) AS refund_amount,
    rr.completed_at,
    rr.created_at
FROM refund_records rr
INNER JOIN offerings o ON o.id = rr.offering_id
INNER JOIN properties p ON p.id = o.property_id
LEFT JOIN users u ON u.id = rr.user_id
SQL);

        DB::statement(<<<'SQL'
CREATE VIEW business_cashflow_monthly_overview AS
SELECT
    month_start,
    SUM(deposit_gross_amount) AS deposit_gross_amount,
    SUM(deposit_net_amount) AS deposit_net_amount,
    SUM(deposit_site_fee) AS deposit_site_fee,
    SUM(withdrawal_gross_amount) AS withdrawal_gross_amount,
    SUM(withdrawal_net_amount) AS withdrawal_net_amount,
    SUM(withdrawal_site_fee) AS withdrawal_site_fee,
    SUM(deposit_net_amount - withdrawal_net_amount) AS net_platform_cashflow,
    SUM(deposit_count) AS deposit_count,
    SUM(withdrawal_count) AS withdrawal_count
FROM (
    SELECT
        DATE_FORMAT(created_at, '%Y-%m-01') AS month_start,
        SUM(gross_amount) AS deposit_gross_amount,
        SUM(net_amount) AS deposit_net_amount,
        SUM(site_fee) AS deposit_site_fee,
        0 AS withdrawal_gross_amount,
        0 AS withdrawal_net_amount,
        0 AS withdrawal_site_fee,
        COUNT(*) AS deposit_count,
        0 AS withdrawal_count
    FROM deposits
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-01')
    UNION ALL
    SELECT
        DATE_FORMAT(created_at, '%Y-%m-01') AS month_start,
        0 AS deposit_gross_amount,
        0 AS deposit_net_amount,
        0 AS deposit_site_fee,
        SUM(gross_amount) AS withdrawal_gross_amount,
        SUM(net_amount) AS withdrawal_net_amount,
        SUM(site_fee) AS withdrawal_site_fee,
        0 AS deposit_count,
        COUNT(*) AS withdrawal_count
    FROM withdrawal
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-01')
) cashflow
GROUP BY month_start
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->dropViews();
    }

    private function dropViews(): void
    {
        foreach ($this->views as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }
    }
};
