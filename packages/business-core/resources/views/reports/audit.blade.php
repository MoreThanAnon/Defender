@extends(config('business-core.layout'))
@section('title', 'Audit history')

@section('content')
<a href="{{ route('bc.reports.index') }}" class="small">← Reports</a>
<h1 class="h4 mt-1">Audit history</h1>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
    <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th><th>IP</th></tr></thead>
    <tbody>
    @forelse ($events as $event)
        <tr>
            <td class="text-nowrap">{{ $event->created_at->format('j M Y H:i:s') }}</td>
            <td>{{ $event->user ? \BusinessCore\Support\CurrentBusiness::displayName($event->user) : 'system' }}</td>
            <td><code>{{ $event->action }}</code></td>
            <td>{{ $event->subject_type }}@if ($event->subject_id) #{{ $event->subject_id }}@endif</td>
            <td class="text-break">{{ $event->meta ? json_encode($event->meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</td>
            <td>{{ $event->ip }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">Nothing recorded yet.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $events->links('pagination::bootstrap-5') }}</div>
@endsection
