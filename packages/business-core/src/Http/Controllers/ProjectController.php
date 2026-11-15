<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\Asset;
use BusinessCore\Models\Document;
use BusinessCore\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        $this->allow('view');

        return $this->view('projects.index', [
            'projects' => Project::with('asset')->orderByRaw("status = 'done'")->orderBy('name')->get(),
            'assets' => Asset::withCount('projects')->orderBy('name')->get(),
            'showMoney' => $this->current()->can('reports'),
        ]);
    }

    public function store(Request $request)
    {
        $this->allow('operate');

        $project = Project::create($this->validated($request));
        $this->audit('project.created', $project, ['name' => $project->name, 'budget' => $project->budget_amount]);

        return redirect()->route('bc.projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project)
    {
        $this->allow('view');
        $showMoney = $this->current()->can('reports');

        return $this->view('projects.show', [
            'project' => $project->load('asset'),
            'assets' => Asset::orderBy('name')->get(),
            'showMoney' => $showMoney,
            'transactions' => $showMoney ? $project->transactions()->orderByDesc('booked_on')->get() : collect(),
            'payments' => $project->paymentTasks()->latest()->get(),
            'tasks' => $project->tasks()->orderBy('due_date')->get(),
            'proposals' => $project->proposals()->latest()->get(),
            'documents' => Document::where('attached_type', Project::class)->where('attached_id', $project->id)->latest()->get(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $this->allow('operate');

        $project->update($this->validated($request));
        $this->audit('project.updated', $project, $project->getChanges());

        return back()->with('status', 'Project saved.');
    }

    public function storeAsset(Request $request)
    {
        $this->allow('operate');

        $asset = Asset::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(array_keys(config('business-core.asset_kinds')))],
            'location' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));
        $this->audit('asset.created', $asset, ['name' => $asset->name]);

        return back()->with('status', 'Added to the asset register.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'asset_id' => ['nullable', $this->owned('business_assets')],
            'status' => ['required', Rule::in(array_keys(Project::STATUSES))],
            'budget_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
