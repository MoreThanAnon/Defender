<?php

namespace BusinessCore\Support;

/** One bank statement line after parsing. Amounts are integer cents; money in is positive. */
final class ParsedRow
{
    public function __construct(
        public readonly string $date,          // Y-m-d
        public readonly string $description,
        public readonly int $amountCents,
        public readonly ?int $balanceCents,
        public readonly array $raw,            // header => original cell text
    ) {
    }
}
