<?php

namespace BusinessCore\Support;

use RuntimeException;

/** A statement file was rejected. Nothing was imported. */
class ImportException extends RuntimeException
{
    /** @param string[] $problems */
    public function __construct(string $message, public readonly array $problems = [])
    {
        parent::__construct($message);
    }
}
