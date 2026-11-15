@extends(config('business-core.layout'))
@section('title', 'Settings')

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('bc.settings.update') }}" class="card"><div class="card-body">
            @csrf @method('PUT')
            <h2 class="h5">Business details</h2>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name', $business->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="type">Type</label>
                    <select class="form-select" id="type" name="type">
                        @foreach (config('business-core.types') as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $business->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="opening_balance">Opening bank balance</label>
                    <input class="form-control" id="opening_balance" name="opening_balance" type="number" step="0.01" value="{{ old('opening_balance', $business->opening_balance) }}">
                    <div class="form-text">Typed in by you. Shown as "entered by hand" until a statement with balances is imported.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="opening_balance_date">Balance date</label>
                    <input class="form-control" id="opening_balance_date" name="opening_balance_date" type="date" value="{{ old('opening_balance_date', $business->opening_balance_date?->toDateString()) }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="bank_url">Your bank's sign-in page</label>
                    <input class="form-control" id="bank_url" name="bank_url" type="url" value="{{ old('bank_url', $business->bank_url) }}" placeholder="https://">
                    <div class="form-text">Only a shortcut link for payment tasks. Never enter banking passwords here.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="quorum_percent">Vote quorum %</label>
                    <input class="form-control" id="quorum_percent" name="quorum_percent" type="number" min="0" max="100" value="{{ old('quorum_percent', $business->quorum_percent) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $business->notes) }}</textarea>
                </div>
            </div>
            <button class="btn btn-primary mt-3">Save</button>
        </div></form>

        @bizcan('export')
            <div class="card mt-4"><div class="card-body">
                <h2 class="h5">Take your data with you</h2>
                <p class="small text-muted">One zip with every record as CSV, plus your original statements and documents.</p>
                <a class="btn btn-outline-primary" href="{{ route('bc.reports.export') }}">Download everything</a>
            </div></div>
        @endbizcan
    </div>

    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h2 class="h5">Storage and live</h2>
            <dl class="row small mb-2">
                <dt class="col-5">Status</dt><dd class="col-7">{{ ucfirst($business->status) }}</dd>
                <dt class="col-5">Registered name</dt><dd class="col-7">{{ $business->legal_name ?: '—' }}</dd>
                <dt class="col-5">Registration no.</dt><dd class="col-7">{{ $business->registration_number ?: '—' }}</dd>
                <dt class="col-5">Bank</dt><dd class="col-7">{{ $business->bank_name ?: '—' }} {{ $business->bank_account_label }}</dd>
                @if ($business->went_live_at)<dt class="col-5">Went live</dt><dd class="col-7">{{ $business->went_live_at->format('j M Y') }}</dd>@endif
            </dl>

            @if (! $canGoLive)
                <p class="small text-muted mb-0">Moving a business between storage and live is done by the platform administrator once the company and its bank account have been checked.</p>
            @elseif (! $business->isLive())
                <form method="POST" action="{{ route('bc.go-live') }}">
                    @csrf
                    <p class="small">Link this stored business to the real company it represents.</p>
                    <div class="mb-2"><label class="form-label small" for="gl_legal">Registered company name</label>
                        <input class="form-control form-control-sm" id="gl_legal" name="legal_name" value="{{ old('legal_name', $business->legal_name) }}" required></div>
                    <div class="mb-2"><label class="form-label small" for="gl_reg">Registration number</label>
                        <input class="form-control form-control-sm" id="gl_reg" name="registration_number" value="{{ old('registration_number', $business->registration_number) }}"></div>
                    <div class="mb-2"><label class="form-label small" for="gl_bank">Bank</label>
                        <input class="form-control form-control-sm" id="gl_bank" name="bank_name" value="{{ old('bank_name', $business->bank_name) }}" required></div>
                    <div class="mb-2"><label class="form-label small" for="gl_label">Account label <span class="text-muted">(e.g. "Current ••4821", not the full number)</span></label>
                        <input class="form-control form-control-sm" id="gl_label" name="bank_account_label" value="{{ old('bank_account_label', $business->bank_account_label) }}" maxlength="60"></div>
                    <div class="mb-2"><label class="form-label small" for="gl_url">Bank sign-in page</label>
                        <input class="form-control form-control-sm" id="gl_url" name="bank_url" type="url" value="{{ old('bank_url', $business->bank_url) }}"></div>
                    <div class="form-check small mb-1">
                        <input class="form-check-input" type="checkbox" value="1" id="gl_clear" name="clear_sandbox" checked>
                        <label class="form-check-label" for="gl_clear">Delete practice bank statements so it starts from real data only</label>
                    </div>
                    <div class="form-check small mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="gl_confirm" name="confirmed" required>
                        <label class="form-check-label" for="gl_confirm">I have checked that this company exists and that the bank account is in its own name and under its own control.</label>
                    </div>
                    <button class="btn btn-primary btn-sm">Make live</button>
                </form>
            @endif

            @if ($canGoLive)
                <hr>
                <form method="POST" action="{{ route('bc.status') }}" class="d-flex gap-2">
                    @csrf
                    @if (! $business->isStored())
                        <button class="btn btn-outline-secondary btn-sm" name="status" value="stored">Return to storage</button>
                    @endif
                    @if ($business->status !== 'archived')
                        <button class="btn btn-outline-secondary btn-sm" name="status" value="archived">Archive (read-only)</button>
                    @endif
                </form>
            @endif
        </div></div>
    </div>
</div>
@endsection
