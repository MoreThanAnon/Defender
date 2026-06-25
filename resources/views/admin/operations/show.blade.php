@extends('layouts.app')

@php
    $billLines = collect($checklist->bills_due ?? [])->pluck('label')->implode("\n");
    $maintenanceLines = collect($checklist->maintenance_tasks ?? [])->pluck('label')->implode("\n");
    $requiredDocuments = $checklist->required_documents ?? [];
    $meetingStatus = $checklist->meeting_status ?? [];
    $proposalStatus = $checklist->proposal_status ?? [];
    $legalReview = $checklist->legal_review ?? [];
@endphp

@section('content')
<div class="container mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">{{ $offering->property?->name ?? 'Asset' }} Operations</h1>
            <p class="text-muted mb-0">Offering #{{ $offering->id }} — {{ $offering->name }}</p>
        </div>
        <a href="{{ route('admin.operations.index') }}" class="btn btn-outline-secondary">Back to Operations</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-warning">
        <strong>Human review required:</strong>
        do not mark this investment, asset, property, or business structure as legally compliant from this checklist.
        Use it to prepare solicitor, Central Bank/regulatory, company law, tax, investor-rights,
        AML/KYC, data-protection, and property-ownership reviews.
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Decidim Assembly</div>
                    @if($offering->decidimGovernanceSpace?->decidim_assembly_url)
                        <a href="{{ $offering->decidimGovernanceSpace->decidim_assembly_url }}" target="_blank" rel="noopener">
                            {{ $offering->decidimGovernanceSpace->decidim_assembly_slug ?: 'Open Assembly' }}
                        </a>
                    @else
                        <div class="text-muted">Not created yet</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Equity positions</div>
                    <div class="fs-4 fw-bold">{{ $offering->equityPositions->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Loan notes</div>
                    <div class="fs-4 fw-bold">{{ $offering->loanNotes->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.operations.update', $offering) }}">
        @csrf

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Operating snapshot</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Current bank balance / cash available</label>
                        <input type="number" step="0.01" min="0" name="bank_balance" class="form-control"
                               value="{{ old('bank_balance', $checklist->bank_balance) }}"
                               placeholder="0.00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Bills / costs due</label>
                        <textarea name="bills_due_text" class="form-control" rows="5"
                                  placeholder="One cost per line, e.g. rent roll issue, insurance renewal, rates, loan payment, utility bill">{{ old('bills_due_text', $billLines) }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Asset tasks / maintenance / due diligence</label>
                        <textarea name="maintenance_tasks_text" class="form-control" rows="5"
                                  placeholder="One task per line, e.g. repair boiler, inspect roof, review lease, planning search, stock/fit-out check">{{ old('maintenance_tasks_text', $maintenanceLines) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Required asset documents</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Tick these only when the document or evidence has been gathered for this specific asset.
                    These examples are intentionally asset-neutral, so the same workflow can cover rental houses,
                    shops, land, businesses, equipment, or any other fundable purchase.
                </p>
                <div class="row">
                    @foreach($assetDocumentItems as $key => $item)
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="form-check">
                                    <input type="hidden" name="required_documents[{{ $key }}]" value="0">
                                    <input class="form-check-input" type="checkbox" name="required_documents[{{ $key }}]" value="1" id="document_{{ $key }}"
                                           @checked(old("required_documents.$key", $requiredDocuments[$key] ?? false))>
                                    <label class="form-check-label fw-semibold" for="document_{{ $key }}">
                                        {{ $item['label'] }}
                                    </label>
                                </div>
                                <div class="text-muted small mt-2">{{ $item['description'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">First meeting status</h5>
                    </div>
                    <div class="card-body">
                        @foreach([
                            'first_meeting_scheduled' => 'First investor meeting scheduled',
                            'agenda_published' => 'Agenda and briefing papers published',
                            'minutes_published' => 'Minutes/actions published after meeting',
                        ] as $key => $label)
                            <div class="form-check mb-2">
                                <input type="hidden" name="meeting_status[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="meeting_status[{{ $key }}]" value="1" id="meeting_{{ $key }}"
                                       @checked(old("meeting_status.$key", $meetingStatus[$key] ?? false))>
                                <label class="form-check-label" for="meeting_{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Proposal status</h5>
                    </div>
                    <div class="card-body">
                        @foreach([
                            'operating_plan_proposed' => '90-day operating plan proposal created',
                            'maintenance_budget_proposed' => 'Maintenance budget proposal created',
                            'bank_reporting_proposed' => 'Bank balance/reporting proposal created',
                        ] as $key => $label)
                            <div class="form-check mb-2">
                                <input type="hidden" name="proposal_status[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="proposal_status[{{ $key }}]" value="1" id="proposal_{{ $key }}"
                                       @checked(old("proposal_status.$key", $proposalStatus[$key] ?? false))>
                                <label class="form-check-label" for="proposal_{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Legal / compliance review placeholders</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Tick only when a qualified human reviewer has actually completed that review outside this workflow.</p>
                <div class="row">
                    @foreach([
                        'solicitor_review' => 'Solicitor review',
                        'central_bank_review' => 'Central Bank / crowdfunding regulation review',
                        'company_law_review' => 'Company law and governance documents review',
                        'investor_rights_review' => 'Investor rights and voting rights review',
                        'tax_review' => 'Tax review',
                        'property_structure_review' => 'Property/SPV/title/security structure review',
                        'data_protection_review' => 'Data protection and Decidim participation data review',
                    ] as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input type="hidden" name="legal_review[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" name="legal_review[{{ $key }}]" value="1" id="legal_{{ $key }}"
                                       @checked(old("legal_review.$key", $legalReview[$key] ?? false))>
                                <label class="form-check-label" for="legal_{{ $key }}">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Admin notes</h5>
            </div>
            <div class="card-body">
                <textarea name="admin_notes" class="form-control" rows="5"
                          placeholder="Operational notes, risks, open questions, documents still needed">{{ old('admin_notes', $checklist->admin_notes) }}</textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save asset operations checklist</button>
    </form>
</div>
@endsection
