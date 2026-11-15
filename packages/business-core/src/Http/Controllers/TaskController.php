<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\Membership;
use BusinessCore\Models\Project;
use BusinessCore\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index()
    {
        $this->allow('view');

        return $this->view('tasks.index', [
            'tasks' => Task::with(['project', 'assignee'])->orderByRaw("status = 'done'")->orderByRaw('due_date is null')->orderBy('due_date')->get(),
            'projects' => Project::orderBy('name')->get(),
            'people' => Membership::with('user')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->allow('operate');

        $task = Task::create($this->validated($request) + ['created_by' => $request->user()->getKey()]);
        $this->audit('task.created', $task, ['title' => $task->title]);

        return back()->with('status', 'Task added.');
    }

    public function update(Request $request, Task $task)
    {
        // Operators manage tasks; the person a task is assigned to may also update it.
        abort_unless($this->current()->can('operate') || (int) $task->assigned_to === (int) $request->user()->getKey(), 403);

        $data = $this->validated($request);
        $data['completed_at'] = $data['status'] === 'done' ? ($task->completed_at ?? now()) : null;

        $task->update($data);
        $this->audit('task.updated', $task, ['status' => $task->status]);

        return back()->with('status', 'Task saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'project_id' => ['nullable', $this->owned('business_projects')],
            'assigned_to' => ['nullable', Rule::exists('business_memberships', 'user_id')->where('business_id', $this->biz()->id)],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
        ]);
    }
}
