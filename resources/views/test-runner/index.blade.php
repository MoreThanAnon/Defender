@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Test Runner</h1>
            <p class="text-muted mb-0">Run local business-rule simulations and inspect their outputs.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Property Purchase Simulation</h2>
                    <form id="purchaseSimulationForm">
                        @csrf
                        <div class="mb-3">
                            <label for="property_id" class="form-label">Property</label>
                            <select class="form-select" id="property_id" name="property_id">
                                @foreach($properties as $property)
                                    <option value="{{ $property->id }}" @selected(optional($defaultProperty)->id === $property->id)>
                                        #{{ $property->id }} - {{ $property->name }} - EUR {{ number_format((float) $property->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="startButton" @disabled($properties->isEmpty())>
                                Reset and Run 1-Minute Simulation
                            </button>
                        </div>
                        <p class="small text-muted mb-0 mt-2">
                            Starting clears existing bids, restores those bid amounts to users, simulates one minute of purchases across every user, and settles the offering immediately.
                        </p>
                    </form>

                    <hr>

                    <dl class="row small mb-0">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7" id="runStatus">Idle</dd>
                        <dt class="col-5">Property</dt>
                        <dd class="col-7" id="propertyStatus">Not resolved</dd>
                        <dt class="col-5">Offering</dt>
                        <dd class="col-7" id="offeringStatus">Not resolved</dd>
                        <dt class="col-5">Org steward</dt>
                        <dd class="col-7" id="orgSteward">Not seeded</dd>
                        <dt class="col-5">Users</dt>
                        <dd class="col-7" id="runUsers">0 / 100</dd>
                        <dt class="col-5">Funded</dt>
                        <dd class="col-7" id="runFunded">EUR 0.00</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h2 class="h5 mb-0">Progress</h2>
                        <span class="small text-muted" id="runTimer">00:00 / 01:00 simulated</span>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Simulation progress">
                        <div class="progress-bar" id="runProgress" style="width: 0%">0%</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Output</h2>
                    <pre id="runLog" class="bg-dark text-light rounded p-3 mb-0" style="height: 260px; overflow: auto;">Ready.</pre>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h5 mb-0">Ownership</h2>
                        <span class="small text-muted" id="ownershipCount">0 users</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th class="text-end">Invested</th>
                                    <th class="text-end">Ownership</th>
                                    <th>Position</th>
                                    <th>Local</th>
                                </tr>
                            </thead>
                            <tbody id="ownershipRows">
                                <tr>
                                    <td colspan="6" class="text-muted">Run the simulation to populate ownership.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h5 mb-0">Settlement Records</h2>
                        <span class="small text-muted" id="recordCounts">0 records</span>
                    </div>

                    <div class="row g-4">
                        <div class="col-xl-7">
                            <h3 class="h6">Equity Positions</h3>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Holder</th>
                                            <th>Type</th>
                                            <th class="text-end">Economic</th>
                                            <th class="text-end">Voting</th>
                                            <th>Votes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="equityRows">
                                        <tr><td colspan="5" class="text-muted">No equity records yet.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-xl-5">
                            <h3 class="h6">Loan Notes</h3>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Lender</th>
                                            <th class="text-end">Principal</th>
                                            <th>Scope</th>
                                        </tr>
                                    </thead>
                                    <tbody id="loanRows">
                                        <tr><td colspan="3" class="text-muted">No loan notes yet.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted mt-3" id="eventSummary">Refunds: 0 | Funding events: 0</div>
                            <hr>
                            <dl class="row small mb-0" id="settlementTotals">
                                <dt class="col-7">Local investor equity</dt>
                                <dd class="col-5 text-end">0%</dd>
                                <dt class="col-7">Org reserve</dt>
                                <dd class="col-5 text-end">0%</dd>
                                <dt class="col-7">Treasury</dt>
                                <dd class="col-5 text-end">0%</dd>
                                <dt class="col-7">Total equity</dt>
                                <dd class="col-5 text-end">0%</dd>
                                <dt class="col-7">Voting equity</dt>
                                <dd class="col-5 text-end">0%</dd>
                                <dt class="col-7">Loan principal</dt>
                                <dd class="col-5 text-end">€0.00</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('purchaseSimulationForm');
    const startButton = document.getElementById('startButton');
    const runStatus = document.getElementById('runStatus');
    const runUsers = document.getElementById('runUsers');
    const runFunded = document.getElementById('runFunded');
    const propertyStatus = document.getElementById('propertyStatus');
    const offeringStatus = document.getElementById('offeringStatus');
    const orgSteward = document.getElementById('orgSteward');
    const runTimer = document.getElementById('runTimer');
    const runProgress = document.getElementById('runProgress');
    const runLog = document.getElementById('runLog');
    const ownershipRows = document.getElementById('ownershipRows');
    const ownershipCount = document.getElementById('ownershipCount');
    const recordCounts = document.getElementById('recordCounts');
    const equityRows = document.getElementById('equityRows');
    const loanRows = document.getElementById('loanRows');
    const eventSummary = document.getElementById('eventSummary');
    const settlementTotals = document.getElementById('settlementTotals');
    let pollTimer = null;

    const money = new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        startButton.disabled = true;
        runLog.textContent = 'Starting simulation...';

        const response = await fetch('{{ route('test-runner.purchase-simulation.start') }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: new FormData(form),
        });

        const payload = await response.json();
        if (!response.ok) {
            runLog.textContent = payload.message || 'Unable to start simulation.';
            startButton.disabled = false;
            return;
        }

        render(payload);
        clearInterval(pollTimer);
        pollTimer = setInterval(() => poll(payload.id), 3000);
        poll(payload.id);
    });

    async function poll(runId) {
        try {
            const response = await fetch(`{{ url('/test-runner/purchase-simulation') }}/${runId}`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'Simulation request failed.');
            }
            render(payload);

            if (payload.status !== 'running') {
                clearInterval(pollTimer);
                startButton.disabled = false;
            }
        } catch (error) {
            clearInterval(pollTimer);
            startButton.disabled = false;
            runStatus.textContent = 'failed';
            runLog.textContent = `${runLog.textContent}\nSimulation stopped: ${error.message}`;
        }
    }

    function render(payload) {
        runStatus.textContent = payload.status;
        propertyStatus.textContent = payload.settlement?.property_status_label || 'Not resolved';
        offeringStatus.textContent = payload.settlement?.offering_status || 'Not resolved';
        orgSteward.textContent = payload.settlement?.org_steward || 'Not seeded';
        runUsers.textContent = `${payload.progress.users_processed} / ${payload.progress.users_total}`;
        runFunded.textContent = `${money.format(payload.property.funded)} / ${money.format(payload.property.price)} (${payload.property.funded_pct}%)`;

        const elapsed = formatSeconds(payload.progress.elapsed_seconds);
        const duration = formatSeconds(payload.progress.duration_seconds);
        runTimer.textContent = `${elapsed} / ${duration}`;
        runProgress.style.width = `${payload.progress.pct}%`;
        runProgress.textContent = `${Math.round(payload.progress.pct)}%`;

        runLog.textContent = payload.logs.join("\n");
        runLog.scrollTop = runLog.scrollHeight;

        ownershipCount.textContent = `${payload.ownership.length} users`;
        ownershipRows.innerHTML = payload.ownership.length
            ? payload.ownership.map(row => `
                <tr>
                    <td>${escapeHtml(row.username)}</td>
                    <td class="text-muted">${escapeHtml(row.email || '')}</td>
                    <td class="text-end">${money.format(row.amount)}</td>
                    <td class="text-end">${row.ownership_pct}%</td>
                    <td>${escapeHtml(row.position || '')}</td>
                    <td>${row.is_local ? 'Yes' : 'No'}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="6" class="text-muted">No purchases yet.</td></tr>';

        const equity = payload.settlement?.equity_positions || [];
        const loans = payload.settlement?.loan_notes || [];
        recordCounts.textContent = `${equity.length} equity | ${loans.length} loan notes`;
        equityRows.innerHTML = equity.length
            ? equity.map(row => `
                <tr>
                    <td>${escapeHtml(row.holder)}</td>
                    <td>${escapeHtml(row.position_type)}</td>
                    <td class="text-end">${(row.economic_bp / 100).toFixed(2)}%</td>
                    <td class="text-end">${(row.voting_bp / 100).toFixed(2)}%</td>
                    <td>${row.can_vote ? 'Yes' : 'No'}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="5" class="text-muted">No equity records yet.</td></tr>';
        loanRows.innerHTML = loans.length
            ? loans.map(row => `
                <tr>
                    <td>${escapeHtml(row.lender)}</td>
                    <td class="text-end">${money.format(row.principal_cents / 100)}</td>
                    <td>${escapeHtml(row.voting_scope || '')}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="3" class="text-muted">No loan notes yet.</td></tr>';
        eventSummary.textContent = `Refunds: ${payload.settlement?.refund_count || 0} | Funding events: ${payload.settlement?.funding_event_count || 0}`;
        const totals = payload.settlement?.totals || {};
        settlementTotals.innerHTML = `
            <dt class="col-7">Local investor equity</dt>
            <dd class="col-5 text-end">${formatPct(totals.local_investor_economic_pct || 0)}</dd>
            <dt class="col-7">Org reserve</dt>
            <dd class="col-5 text-end">${formatPct(totals.org_reserve_economic_pct || 0)}</dd>
            <dt class="col-7">Treasury</dt>
            <dd class="col-5 text-end">${formatPct(totals.treasury_economic_pct || 0)}</dd>
            <dt class="col-7">Total equity</dt>
            <dd class="col-5 text-end">${formatPct(totals.total_equity_economic_pct || 0)}</dd>
            <dt class="col-7">Voting equity</dt>
            <dd class="col-5 text-end">${formatPct(totals.total_voting_pct || 0)}</dd>
            <dt class="col-7">Loan principal</dt>
            <dd class="col-5 text-end">${money.format((totals.loan_principal_cents || 0) / 100)}</dd>
        `;
    }

    function formatPct(value) {
        const rounded = Math.round(Number(value) * 10000) / 10000;
        return `${rounded}%`;
    }

    function formatSeconds(seconds) {
        const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
        const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
        return `${mins}:${secs}`;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();
</script>
@endsection
