<?php

namespace BusinessCore\Support;

class Format
{
    private const SYMBOLS = ['EUR' => '€', 'GBP' => '£', 'USD' => '$', 'KES' => 'KSh '];

    /** "€1,234.50", or a dash when there is no figure. Output is HTML-safe. */
    public static function money(mixed $amount, ?string $currency = null): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $currency ??= app(CurrentBusiness::class)->get()?->currency ?? 'EUR';
        $symbol = self::SYMBOLS[$currency] ?? $currency.' ';
        $value = (float) $amount;

        return e(($value < 0 ? '−' : '').$symbol.number_format(abs($value), 2));
    }
}
