@extends(config('business-core.layout'))
@section('title', 'Reports')

@section('content')
<h1 class="h4">Reports</h1>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card"><div class="card-header bg-white fw-semibold">Monthly management report</div>
            <table class="table mb-0 align-middle">
                @foreach ($months as $month)
                    <tr>
                        <td><a href="{{ route('bc.reports.monthly', ['period' => $month]) }}">{{ \Illuminate\Support\Carbon::createFromFormat('!Y-m', $month)->format('F Y') }}</a></td>
                        <td class="small">
                            @if ($signoffs->has($month))
                                <span class="badge text-bg-success">Signed off</span> {{ \BusinessCore\Support\CurrentBusiness::displayName($signoffs[$month]->signer) }}, {{ $signoffs[$month]->signed_at->format('j M Y') }}
                            @else
                                <span class="badge text-bg-light border">Not signed off</span>
                            @endif
                        </td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('bc.reports.monthly.csv', ['period' => $month]) }}">CSV</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6">Audit history</h2>
            <p class="small text-muted">Who did what and when. Entries cannot be edited or deleted.</p>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('bc.reports.audit') }}">View audit history</a>
        </div></div>
        @bizcan('export')
            <div class="card"><div class="card-body">
                <h2 class="h6">Full export</h2>
                <p class="small text-muted">Everything as CSV plus original statements and documents, in one zip.</p>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('bc.reports.export') }}">Download everything</a>
            </div></div>
        @endbizcan
    </div>
</div>
@endsection
