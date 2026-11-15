<?php

namespace BusinessCore\Contracts;

use BusinessCore\Support\ParsedRow;

/**
 * Turns a bank export file into statement lines. Bind your own implementation
 * in a service provider to support another bank format (OFX, CAMT, ...).
 */
interface StatementParser
{
    /**
     * @return array{rows: ParsedRow[], errors: string[]}  rows in the order they appear in the file
     */
    public function parse(string $path): array;
}
