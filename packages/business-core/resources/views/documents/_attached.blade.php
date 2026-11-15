{{-- Files attached to one record, with an upload form. Expects $documents, $attachTo, $attachId. --}}
<div class="card mb-3"><div class="card-header bg-white fw-semibold">Files</div>
    <ul class="list-group list-group-flush">
        @forelse ($documents as $document)
            <li class="list-group-item d-flex">
                <a href="{{ route('bc.documents.download', $document) }}">{{ $document->title }}</a>
                <span class="ms-auto small text-muted">{{ $document->kindLabel() }} · {{ $document->created_at->format('j M Y') }}</span>
            </li>
        @empty
            <li class="list-group-item text-muted">No files attached.</li>
        @endforelse
    </ul>
    @bizcan('documents')
        <div class="card-body border-top">
            <form method="POST" action="{{ route('bc.documents.store') }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <input type="hidden" name="attach_to" value="{{ $attachTo }}">
                <input type="hidden" name="attach_id" value="{{ $attachId }}">
                <div class="col-md-4"><input class="form-control form-control-sm" name="title" placeholder="Title, e.g. Supplier quote" required aria-label="Document title"></div>
                <div class="col-md-3"><select class="form-select form-select-sm" name="kind" aria-label="Document kind">
                    @foreach (config('business-core.document_kinds') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></div>
                <div class="col-md-3"><input class="form-control form-control-sm" type="file" name="file" required aria-label="File"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Attach</button></div>
            </form>
        </div>
    @endbizcan
</div>
