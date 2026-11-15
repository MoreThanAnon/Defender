{{-- Expects $balance from Business::balanceSnapshot(). Always shows where the figure came from and how old it is. --}}
<div class="bc-source">Bank balance</div>
@if ($balance['amount'] === null)
    <div class="bc-figure text-muted">—</div>
    <div class="small text-muted">No statement imported and no opening balance entered yet.</div>
@else
    <div class="bc-figure">@money($balance['amount'])</div>
    <div class="small">
        <span class="badge text-bg-light border">
            @switch($balance['source'])
                @case('statement') From imported bank statement @break
                @case('calculated') Calculated: opening balance + imported lines @break
                @default Opening balance entered by hand
            @endswitch
        </span>
        as of <strong>{{ $balance['as_of']?->format('j M Y') ?? 'unknown date' }}</strong>
    </div>
    @if ($balance['stale'])
        <div class="small text-danger mt-1">Stale: no bank data newer than {{ config('business-core.stale_import_days') }} days. Import a recent statement.</div>
    @endif
    <div class="small text-muted mt-1">A snapshot, not live available cash. Your bank is the authority.</div>
@endif
