<?php

namespace App\Actions\Offerings;

use App\Models\Offering;

class SettleOffering
{
    public function __construct(private ?ResolveOfferingOutcomeAction $resolver = null) {}

    public function handle(Offering $offering, int $equityClassId, int $orgUserId = null): void
    {
        ($this->resolver ?: new ResolveOfferingOutcomeAction())->handle($offering, $equityClassId, $orgUserId);
    }
}
