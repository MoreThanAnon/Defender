@extends(config('business-core.layout'))
@section('title', 'Records')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <h1 class="h4">Company records</h1>
        <div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
            <thead><tr><th>Document</th><th>Kind</th><th>Attached to</th><th>Uploaded</th></tr></thead>
            <tbody>
            @forelse ($documents as $document)
                <tr>
                    <td><a href="{{ route('bc.documents.download', $document) }}">{{ $document->title }}</a>
                        <div class="small text-muted">{{ $document->original_name }} · {{ number_format($document->size / 1024) }} KB</div></td>
                    <td>{{ $document->kindLabel() }}</td>
                    <td class="small">{{ $document->attached?->title ?? $document->attached?->name ?? $document->attached?->payee_name ?? '—' }}</td>
                    <td class="small">{{ \BusinessCore\Support\CurrentBusiness::displayName($document->uploader) }}<br>{{ $document->created_at->format('j M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No records yet. Keep minutes, contracts, bills and policies here.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    </div>
    @bizcan('documents')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.documents.store') }}" enctype="multipart/form-data" class="card"><div class="card-body">
            @csrf
            <h2 class="h6">Upload a record</h2>
            <div class="mb-2"><label class="form-label small mb-0" for="title">Title</label>
                <input class="form-control form-control-sm" id="title" name="title" required></div>
            <div class="mb-2"><label class="form-label small mb-0" for="kind">Kind</label>
                <select class="form-select form-select-sm" id="kind" name="kind">
                    @foreach (config('business-core.document_kinds') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></div>
            <div class="mb-3"><label class="form-label small mb-0" for="file">File</label>
                <input class="form-control form-control-sm" id="file" type="file" name="file" required>
                <div class="form-text">Up to {{ round(config('business-core.upload_max_kb') / 1024) }} MB. Stored privately; only people in this business can download it.</div></div>
            <button class="btn btn-primary btn-sm">Upload</button>
        </div></form>
    </div>
    @endbizcan
</div>
@endsection
