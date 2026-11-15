<?php

namespace BusinessCore\Models;

class Task extends TenantModel
{
    public const STATUSES = ['todo' => 'To do', 'doing' => 'In progress', 'done' => 'Done'];

    protected $table = 'business_tasks';

    protected $casts = ['due_date' => 'date', 'completed_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->userRelation('assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'done' && $this->due_date !== null && $this->due_date->lt(today());
    }
}
