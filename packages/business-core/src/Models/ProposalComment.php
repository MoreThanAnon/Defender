<?php

namespace BusinessCore\Models;

use Illuminate\Database\Eloquent\Model;

/** Reached only through its proposal, which carries the tenant boundary. */
class ProposalComment extends Model
{
    protected $table = 'business_proposal_comments';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(config('business-core.user_model'), 'user_id');
    }
}
