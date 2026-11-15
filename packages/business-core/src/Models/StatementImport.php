<?php

namespace BusinessCore\Models;

class StatementImport extends TenantModel
{
    protected $table = 'business_statement_imports';

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'closing_balance' => 'decimal:2'];

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function importer()
    {
        return $this->userRelation('imported_by');
    }
}
