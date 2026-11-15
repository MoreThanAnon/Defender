@extends(config('business-core.layout'))
@section('title', 'Tasks')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <h1 class="h4">Tasks</h1>
        <div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
            <thead><tr><th>Task</th><th>Owner</th><th>Due</th><th style="width: 13rem">Status</th></tr></thead>
            <tbody>
            @forelse ($tasks as $task)
                @php($canEdit = app(\BusinessCore\Support\CurrentBusiness::class)->can('operate') || (int) $task->assigned_to === (int) auth()->id())
                <tr class="{{ $task->status === 'done' ? 'text-muted' : '' }}">
                    <td>{{ $task->title }}
                        <div class="small text-muted">{{ $task->project?->name }} {{ $task->notes }}</div></td>
                    <td>{{ $task->assignee ? \BusinessCore\Support\CurrentBusiness::displayName($task->assignee) : '—' }}</td>
                    <td class="text-nowrap {{ $task->isOverdue() ? 'text-danger fw-bold' : '' }}">{{ $task->due_date?->format('j M Y') ?? '—' }}</td>
                    <td>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('bc.tasks.update', $task) }}" class="d-flex gap-1">
                                @csrf @method('PUT')
                                <input type="hidden" name="title" value="{{ $task->title }}">
                                <input type="hidden" name="notes" value="{{ $task->notes }}">
                                <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                                <input type="hidden" name="assigned_to" value="{{ $task->assigned_to }}">
                                <input type="hidden" name="due_date" value="{{ $task->due_date?->toDateString() }}">
                                <select class="form-select form-select-sm" name="status" aria-label="Status of {{ $task->title }}">
                                    @foreach (\BusinessCore\Models\Task::STATUSES as $value => $label)<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        @else
                            {{ \BusinessCore\Models\Task::STATUSES[$task->status] }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No tasks yet.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    </div>

    @bizcan('operate')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.tasks.store') }}" class="card"><div class="card-body">
            @csrf
            <input type="hidden" name="status" value="todo">
            <h2 class="h6">New task</h2>
            <div class="mb-2"><label class="form-label small mb-0" for="title">Task</label>
                <input class="form-control form-control-sm" id="title" name="title" required placeholder="e.g. Site inspection"></div>
            <div class="mb-2"><label class="form-label small mb-0" for="notes">Notes</label>
                <input class="form-control form-control-sm" id="notes" name="notes"></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label small mb-0" for="assigned_to">Owner</label>
                    <select class="form-select form-select-sm" id="assigned_to" name="assigned_to"><option value="">Nobody yet</option>
                        @foreach ($people as $person)<option value="{{ $person->user_id }}">{{ \BusinessCore\Support\CurrentBusiness::displayName($person->user) }}</option>@endforeach
                    </select></div>
                <div class="col-6"><label class="form-label small mb-0" for="due_date">Due</label>
                    <input class="form-control form-control-sm" id="due_date" name="due_date" type="date"></div>
                <div class="col-12"><label class="form-label small mb-0" for="project_id">Project</label>
                    <select class="form-select form-select-sm" id="project_id" name="project_id"><option value="">None</option>
                        @foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach
                    </select></div>
            </div>
            <button class="btn btn-primary btn-sm mt-3">Add task</button>
        </div></form>
    </div>
    @endbizcan
</div>
@endsection
