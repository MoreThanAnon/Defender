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

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please check the form:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
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
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Uploaded asset documents</h5>
                <span class="badge bg-warning text-dark">Private admin files</span>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Upload supporting documents against the checklist. Uploading a file marks that document type as gathered,
                    but it does not mean the document has been legally, tax, regulatory, or commercially approved.
                    Investor visibility is off by default and should only be enabled after review.
                </p>

                <form method="POST" action="{{ route('admin.operations.documents.store', $offering) }}" enctype="multipart/form-data" class="border rounded p-3 mb-4">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Document type</label>
                            <select name="document_key" class="form-select" required>
                                @foreach($assetDocumentItems as $key => $item)
                                    <option value="{{ $key }}" @selected(old('document_key') === $key)>{{ $item['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Display title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Optional">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">File</label>
                            <input type="file" name="document" class="form-control" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <button type="submit" class="btn btn-outline-primary w-100">Upload</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="What this file is, who supplied it, and what review is still needed">{{ old('notes') }}</textarea>
                            <div class="form-text">Allowed: PDF, Word, Excel, CSV, images, TXT. Max 10MB.</div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>File</th>
                                <th>Status</th>
                                <th>Uploaded</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($offering->assetDocuments->sortByDesc('created_at') as $document)
                                <tr>
                                    <td>{{ $assetDocumentItems[$document->document_key]['label'] ?? $document->document_key }}</td>
                                    <td>
                                        <div>{{ $document->title }}</div>
                                        @if($document->notes)
                                            <div class="text-muted small">{{ $document->notes }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $document->original_filename }}</div>
                                        <div class="text-muted small">{{ number_format($document->size_bytes / 1024, 1) }} KB</div>
                                    </td>
                                    <td style="min-width: 220px;">
                                        <form method="POST" action="{{ route('admin.operations.documents.update', [$offering, $document]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="review_status" class="form-select form-select-sm mb-2">
                                                @foreach([
                                                    'needs_review' => 'Needs review',
                                                    'reviewed' => 'Reviewed',
                                                    'changes_needed' => 'Changes needed',
                                                ] as $status => $label)
                                                    <option value="{{ $status }}" @selected($document->review_status === $status)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="notes" class="form-control form-control-sm mb-2"
                                                   value="{{ $document->notes }}"
                                                   placeholder="Reviewer note">
                                            <div class="form-check mb-2">
                                                <input type="hidden" name="visible_to_investors" value="0">
                                                <input class="form-check-input" type="checkbox" name="visible_to_investors" value="1" id="visible_{{ $document->id }}"
                                                       @checked($document->visible_to_investors)
                                                       @disabled($document->review_status !== 'reviewed')>
                                                <label class="form-check-label small" for="visible_{{ $document->id }}">
                                                    Visible to investors after review
                                                </label>
                                            </div>
                                            <input type="text" name="publication_notes" class="form-control form-control-sm mb-2"
                                                   value="{{ $document->publication_notes }}"
                                                   placeholder="Investor publication note">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">Update review</button>
                                            @if($document->reviewed_at)
                                                <div class="text-muted small mt-1">Reviewed {{ $document->reviewed_at->diffForHumans() }}</div>
                                            @endif
                                            @if($document->published_at)
                                                <div class="text-success small mt-1">Investor-visible {{ $document->published_at->diffForHumans() }}</div>
                                            @elseif($document->visible_to_investors)
                                                <div class="text-warning small mt-1">Visibility pending reviewed status.</div>
                                            @else
                                                <div class="text-muted small mt-1">Admin-only</div>
                                            @endif
                                        </form>
                                    </td>
                                    <td>
                                        {{ $document->created_at?->diffForHumans() }}
                                        @if($document->uploader)
                                            <div class="text-muted small">by {{ $document->uploader->name ?? $document->uploader->email }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.operations.documents.download', [$offering, $document]) }}" class="btn btn-outline-secondary btn-sm">Download</a>
                                        <form method="POST" action="{{ route('admin.operations.documents.destroy', [$offering, $document]) }}" class="d-inline"
                                              onsubmit="return confirm('Delete this uploaded document?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">No documents uploaded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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

    <div class="card shadow-sm mt-4 mb-4">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Activity / audit trail</h5>
            <span class="badge bg-secondary">Latest 50 events</span>
        </div>
        <div class="card-body">
            <p class="text-muted">
                This log records operational actions for traceability. It is a technical audit trail and does not prove
                legal, regulatory, tax, or investor-rights compliance.
            </p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Summary</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td>
                                    <div>{{ $log->created_at?->format('Y-m-d H:i') }}</div>
                                    <div class="text-muted small">{{ $log->created_at?->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($log->actor)
                                        {{ $log->actor->name ?? $log->actor->email }}
                                        <div class="text-muted small">User #{{ $log->actor->id }}</div>
                                    @else
                                        <span class="text-muted">System / deleted user</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-dark">{{ str_replace('_', ' ', $log->action) }}</span></td>
                                <td>{{ $log->summary }}</td>
                                <td>
                                    @if($log->new_values)
                                        <details>
                                            <summary class="small">View changed values</summary>
                                            <pre class="small bg-light p-2 mt-2 mb-0 rounded">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @else
                                        <span class="text-muted small">No value snapshot</span>
                                    @endif
                                    @if($log->ip_address)
                                        <div class="text-muted small mt-1">IP: {{ $log->ip_address }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">No activity has been logged for this asset yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
