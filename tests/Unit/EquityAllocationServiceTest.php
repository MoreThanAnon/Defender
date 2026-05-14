<?php

namespace Tests\Unit;

use App\Services\EquityAllocationService;
use PHPUnit\Framework\TestCase;

class EquityAllocationServiceTest extends TestCase
{
    public function test_it_allocates_local_equity_and_non_local_loans(): void
    {
        $service = new EquityAllocationService();

        $result = $service->allocate([
            ['investor_id' => 1, 'amount' => '500.00', 'is_local' => true],
            ['investor_id' => 2, 'amount' => '500.00', 'is_local' => true],
            ['investor_id' => 3, 'amount' => '250.00', 'is_local' => false],
        ]);

        $this->assertSame('10.00', $result['org_equity_pct']);
        $this->assertSame(['1' => '45.00', '2' => '45.00'], $result['investor_equity']);
        $this->assertSame(['3' => '250.00'], $result['loan_notes']);
        $this->assertSame('0.00', $result['unallocated_equity_pct']);
    }
}
