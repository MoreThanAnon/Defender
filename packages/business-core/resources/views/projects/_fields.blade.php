<div class="mb-2"><label class="form-label small mb-0" for="p_name">Name</label>
    <input class="form-control form-control-sm" id="p_name" name="name" value="{{ old('name', $project?->name) }}" required maxlength="120"></div>
<div class="mb-2"><label class="form-label small mb-0" for="p_desc">Description</label>
    <textarea class="form-control form-control-sm" id="p_desc" name="description" rows="2">{{ old('description', $project?->description) }}</textarea></div>
<div class="row g-2">
    <div class="col-6"><label class="form-label small mb-0" for="p_budget">Budget</label>
        <input class="form-control form-control-sm" id="p_budget" name="budget_amount" type="number" step="0.01" min="0" value="{{ old('budget_amount', $project?->budget_amount) }}"></div>
    <div class="col-6"><label class="form-label small mb-0" for="p_due">Due</label>
        <input class="form-control form-control-sm" id="p_due" name="due_date" type="date" value="{{ old('due_date', $project?->due_date?->toDateString()) }}"></div>
    <div class="col-6"><label class="form-label small mb-0" for="p_status">Status</label>
        <select class="form-select form-select-sm" id="p_status" name="status">
            @foreach (\BusinessCore\Models\Project::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', $project?->status ?? 'active') === $value)>{{ $label }}</option>@endforeach
        </select></div>
    <div class="col-6"><label class="form-label small mb-0" for="p_asset">Asset</label>
        <select class="form-select form-select-sm" id="p_asset" name="asset_id">
            <option value="">None</option>
            @foreach ($assets as $asset)<option value="{{ $asset->id }}" @selected((int) old('asset_id', $project?->asset_id) === $asset->id)>{{ $asset->name }}</option>@endforeach
        </select></div>
</div>
