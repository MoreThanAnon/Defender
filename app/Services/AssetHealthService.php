<?php

namespace App\Services;

use App\Models\Offering;

class AssetHealthService
{
    /**
     * @return array<int,array{level:string,label:string}>
     */
    public function alertsFor(Offering $offering): array
    {
        $alerts = [];
        $governance = $offering->decidimGovernanceSpace;
        $checklist = $offering->assetOperationsChecklist;

        if (! $governance) {
            $alerts[] = ['level' => 'danger', 'label' => 'No Decidim governance space'];
        } elseif ($governance->status !== 'created') {
            $alerts[] = ['level' => $governance->status === 'failed' ? 'danger' : 'warning', 'label' => 'Decidim sync '.$governance->status];
        }

        if ($governance && ! $governance->decidim_assembly_url) {
            $alerts[] = ['level' => 'warning', 'label' => 'No Assembly URL'];
        }

        if ((int) ($offering->decidim_governance_syncs_count ?? 0) === 0) {
            $alerts[] = ['level' => 'danger', 'label' => 'No investor roles synced'];
        }

        if (! $checklist) {
            $alerts[] = ['level' => 'warning', 'label' => 'Operations checklist not started'];

            return $alerts;
        }

        if ($checklist->bank_balance === null) {
            $alerts[] = ['level' => 'warning', 'label' => 'Bank/cash balance missing'];
        }

        $documents = $checklist->required_documents ?? [];
        $requiredDocuments = [
            'asset_identity',
            'ownership_or_title',
            'valuation_or_price_support',
            'acquisition_agreement',
            'income_or_use_plan',
            'costs_and_liabilities',
            'insurance_and_risk',
            'regulatory_permissions',
            'governance_and_investor_rights',
            'tax_and_structure_review',
        ];

        foreach ($requiredDocuments as $key) {
            if (! ($documents[$key] ?? false)) {
                $alerts[] = ['level' => 'warning', 'label' => 'Asset document pack incomplete'];
                break;
            }
        }

        if ($offering->assetDocuments()->where('review_status', 'needs_review')->exists()) {
            $alerts[] = ['level' => 'warning', 'label' => 'Documents need review'];
        }

        if ($offering->assetDocuments()->where('review_status', 'changes_needed')->exists()) {
            $alerts[] = ['level' => 'danger', 'label' => 'Document changes needed'];
        }

        $meeting = $checklist->meeting_status ?? [];
        if (! ($meeting['first_meeting_scheduled'] ?? false)) {
            $alerts[] = ['level' => 'warning', 'label' => 'First investor meeting not scheduled'];
        }
        if (! ($meeting['agenda_published'] ?? false)) {
            $alerts[] = ['level' => 'warning', 'label' => 'Meeting agenda not published'];
        }

        $proposal = $checklist->proposal_status ?? [];
        if (! ($proposal['operating_plan_proposed'] ?? false)) {
            $alerts[] = ['level' => 'warning', 'label' => '90-day operating plan not proposed'];
        }

        $legal = $checklist->legal_review ?? [];
        $requiredLegal = [
            'solicitor_review',
            'central_bank_review',
            'company_law_review',
            'investor_rights_review',
            'tax_review',
            'property_structure_review',
            'data_protection_review',
        ];

        foreach ($requiredLegal as $key) {
            if (! ($legal[$key] ?? false)) {
                $alerts[] = ['level' => 'warning', 'label' => 'Legal/compliance review incomplete'];
                break;
            }
        }

        return $alerts;
    }
}
