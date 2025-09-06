<?php

namespace App\Services;

/**
 * Integer-only allocator (no Brick/BCMath).
 * - Equity in basis points (bp): 1 bp = 0.01% (10,000 bp = 100.00%)
 * - Money in integer cents
 * - Largest-remainder allocation at a configurable step (default 1 bp = 0.01%)
 */
class EquityAllocationService
{
    /**
     * Public entry point used by controllers/tests.
     *
     * @param array<int,array{investor_id:int, amount:string, is_local:bool}> $bids
     * @param string $orgReservePct      e.g. "10.00"  (org keeps this %)
     * @param string $equityPrecisionPct e.g. "0.01"   (allocation step, multiples of 0.01%)
     *
     * @return array{
     *   org_equity_pct:string,
     *   investor_equity: array<int,string>,
     *   unallocated_equity_pct:string,
     *   loan_notes: array<int,string>,
     *   refunds: array<int,string>
     * }
     */
    /**
     * 
     * $bids = [
*    [
*        'investor_id' => 1,
*        'amount' => '50000.00',
*        'is_local' => true,
*    ],
*];
     */
    public function allocate(
        array $bids,
        string $orgReservePct = '10.00',
        string $equityPrecisionPct = '0.01'
    ): array {
        // 1) Normalize configuration (all integers, no floats)
        [$orgBp, $poolBpTotal, $stepBp] = $this->normalizeConfig($orgReservePct, $equityPrecisionPct);

        // 2) Bucket bids into locals (equity) and non-locals (loans)
        [$locals, $loans, $totalLocalCents] = $this->bucketBids($bids);

        // 3) If no locals, whole pool stays unallocated; only loans are created
        //TODO: Handle case where no locals but loans exist, do we own all the equity?
        if ($totalLocalCents <= 0) {
            return [
                'org_equity_pct'         => $this->fmtPct($orgBp),
                'investor_equity'        => [],
                'unallocated_equity_pct' => $this->fmtPct($poolBpTotal),
                'loan_notes'             => $this->formatLoans($loans),
                'refunds'                => [],
            ];
        }

        // 4) Allocate local equity in discrete steps (largest remainder)
        [$investorEquityBp, $unallocatedBp] = $this->allocateLocalEquityBp(
            $locals,
            $totalLocalCents,
            $poolBpTotal,
            $stepBp
        );

        // 5) Build output maps (strings as expected by your routes)
        return [
            'org_equity_pct'         => $this->fmtPct($orgBp),
            'investor_equity'        => $this->formatInvestorEquity($investorEquityBp),
            'unallocated_equity_pct' => $this->fmtPct($unallocatedBp),
            'loan_notes'             => $this->formatLoans($loans),
            'refunds'                => [], // hook for future refund logic
        ];
    }

    /* =========================
     * ===== Helper blocks =====
     * ========================= */

    /**
     * Convert config strings to integers safely (no floats).
     * - Percent strings to bp using toCents(): "10.00" -> 1000 bp.
     * - Step is at least 1 bp.
     */
    private function normalizeConfig(string $orgReservePct, string $equityPrecisionPct): array
    {
        $orgBp       = max(0, min(10000, $this->toCents($orgReservePct)));        // 0..10000
        $poolBpTotal = 10000 - $orgBp;                                            // investor pool in bp
        $stepBp      = max(1, $this->toCents($equityPrecisionPct));               // step >= 1 bp

        return [$orgBp, $poolBpTotal, $stepBp];
    }

    /**
     * Split bids into locals (equity) and loans (non-locals).
     *
     * @param array<int,array{investor_id:int, amount:string, is_local:bool}> $bids
     * @return array{array<int,int>, array<int,int>, int} [localsCents, loansCents, totalLocalCents]
     */
    private function bucketBids(array $bids): array
    {
        $locals = [];           // userId => cents
        $loans  = [];           // userId => cents
        $totalLocalCents = 0;

        foreach ($bids as $b) {
            $id    = (int) $b['investor_id'];
            $cents = $this->toCents((string) $b['amount']);
            if ($cents < 0) $cents = 0; // guard against negatives

            if (!empty($b['is_local'])) {
                $locals[$id] = ($locals[$id] ?? 0) + $cents;
                $totalLocalCents += $cents;
            } else {
                $loans[$id] = ($loans[$id] ?? 0) + $cents;
            }
        }

        return [$locals, $loans, $totalLocalCents];
    }

    /**
     * Largest remainder allocation in integer steps of bp.
     *
     * @param array<int,int> $locals            userId => cents
     * @return array{array<int,int>, int}       [investorEquityBp, unallocatedBp]
     */
    private function allocateLocalEquityBp(
        array $locals,
        int $totalLocalCents,
        int $poolBpTotal,
        int $stepBp
    ): array {
        // number of discrete steps available
        $stepsTotal = intdiv($poolBpTotal, $stepBp); // any leftover < stepBp becomes unallocated

        // floor steps and track remainders
        $stepsFloor = [];     // userId => steps
        $remainders = [];     // userId => remainder for tie-break
        $sumFloor   = 0;

        foreach ($locals as $id => $cents) {
            // proportional steps = stepsTotal * (cents / totalLocalCents)
            $numerator = $cents * $stepsTotal;           // safe for normal values
            $floor     = intdiv($numerator, $totalLocalCents);
            $rem       = $numerator - ($floor * $totalLocalCents);

            $stepsFloor[$id] = $floor;
            $remainders[$id] = $rem;
            $sumFloor       += $floor;
        }

        // distribute remaining steps to largest remainders
        $remaining = $stepsTotal - $sumFloor;
        if ($remaining > 0) {
            // stable sort by remainder DESC, then by user id ASC (deterministic)
            uksort($remainders, function ($a, $b) use ($remainders) {
                $cmp = $remainders[$b] <=> $remainders[$a];
                return $cmp !== 0 ? $cmp : ($a <=> $b);
            });

            foreach ($remainders as $id => $_) {
                if ($remaining <= 0) break;
                $stepsFloor[$id]++;
                $remaining--;
            }
        }

        // steps -> bp
        $investorEquityBp = [];
        $sumAllocBp = 0;

        foreach ($stepsFloor as $id => $steps) {
            $bp = $steps * $stepBp;
            if ($bp <= 0) continue;
            $investorEquityBp[$id] = $bp;
            $sumAllocBp += $bp;
        }

        $unallocatedBp = max(0, $poolBpTotal - $sumAllocBp);

        return [$investorEquityBp, $unallocatedBp];
    }

    /* ===== Formatting helpers ===== */

    private function formatInvestorEquity(array $investorEquityBp): array
    {
        $out = [];
        foreach ($investorEquityBp as $id => $bp) {
            $out[$id] = $this->fmtPct($bp);
        }
        return $out;
    }

    private function formatLoans(array $loansCents): array
    {
        $out = [];
        foreach ($loansCents as $id => $cents) {
            $out[$id] = $this->fmtMoney($cents);
        }
        return $out;
    }

    private function fmtPct(int $bp): string
    {
        // 1234 bp -> "12.34"
        return number_format($bp / 100, 2, '.', '');
    }

    private function fmtMoney(int $cents): string
    {
        // 123456 -> "1234.56"
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Robust money parser → integer cents (HALF_UP to 2 dp).
     * Accepts EU/US styles: "1,234.56", "1.234,56", "€ 1 234,56", "(99.99)".
     */
    public function toCents(string $input): int
    {
        $s = trim($input);

        // Detect negatives: '-' or parentheses
        $negative = false;
        if ($s !== '' && $s[0] === '-') {
            $negative = true;
            $s = substr($s, 1);
        }
        if (str_starts_with($s, '(') && str_ends_with($s, ')')) {
            $negative = true;
            $s = substr($s, 1, -1);
        }

        // Keep only digits, dot, comma
        $s = preg_replace('/[^0-9\.,]/', '', $s) ?? '';
        if ($s === '') {
            return 0;
        }

        // Use the LAST '.' or ',' as decimal separator
        $lastDot   = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        $sepPos = max($lastDot === false ? -1 : $lastDot, $lastComma === false ? -1 : $lastComma);

        if ($sepPos === -1) {
            // No decimal separator
            $wholeDigits = preg_replace('/\D/', '', $s) ?: '0';
            $cents = (int) $wholeDigits * 100;
            return $negative ? -$cents : $cents;
        }

        $wholePart = substr($s, 0, $sepPos);
        $fracPart  = substr($s, $sepPos + 1);

        $wholeDigits = preg_replace('/\D/', '', $wholePart) ?: '0';
        $fracDigits  = preg_replace('/\D/', '', $fracPart) ?: '';

        $wholeCents = (int) $wholeDigits * 100;

        // HALF_UP rounding to 2 decimals
        $len = strlen($fracDigits);
        if ($len === 0) {
            $fracCents = 0;
        } elseif ($len === 1) {
            $fracCents = ((int) $fracDigits) * 10;
        } else {
            $firstTwo = (int) substr($fracDigits, 0, 2);
            $third    = (int) ($len >= 3 ? $fracDigits[2] : '0');
            $fracCents = $firstTwo + ($third >= 5 ? 1 : 0);
        }

        $cents = $wholeCents + $fracCents;
        return $negative ? -$cents : $cents;
    }
}