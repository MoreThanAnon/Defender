<?php

namespace BusinessCore\Models;

class Document extends TenantModel
{
    /** What a document may be attached to: form value => model. */
    public const ATTACHABLE = ['project' => Project::class, 'proposal' => Proposal::class, 'payment' => PaymentTask::class];

    protected $table = 'business_documents';

    public function attached()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->userRelation('uploaded_by');
    }

    public function kindLabel(): string
    {
        return config('business-core.document_kinds.'.$this->kind, ucfirst($this->kind));
    }
}
