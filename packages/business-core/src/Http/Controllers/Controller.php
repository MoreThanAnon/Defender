<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Contracts\AuditRecorder;
use BusinessCore\Models\Business;
use BusinessCore\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\Rule;

abstract class Controller extends BaseController
{
    use ValidatesRequests;

    protected function current(): CurrentBusiness
    {
        return app(CurrentBusiness::class);
    }

    protected function biz(): Business
    {
        return $this->current()->get();
    }

    /** Stop with 403 unless the person's role in this business has the ability. */
    protected function allow(string $ability): void
    {
        $this->current()->authorize($ability);
    }

    protected function audit(string $action, ?Model $subject = null, array $meta = []): void
    {
        app(AuditRecorder::class)->record($action, $subject, $meta);
    }

    /** Validation rule: the id must be a row of $table that belongs to this business. */
    protected function owned(string $table)
    {
        return Rule::exists($table, 'id')->where('business_id', $this->biz()->id);
    }

    protected function view(string $name, array $data = [])
    {
        return view('business-core::'.$name, $data + ['business' => $this->current()->get()]);
    }
}
