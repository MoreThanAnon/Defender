<?php

namespace BusinessCore\Services;

use BusinessCore\Contracts\StatementParser;
use BusinessCore\Support\ParsedRow;
use DateTimeImmutable;

/**
 * Reads a bank CSV export. Column names are matched against common aliases, so
 * most "Date / Description / Debit / Credit / Balance" style exports work, as
 * does a single signed "Amount" column. Dates are read day-first (Irish/EU).
 */
class CsvStatementParser implements StatementParser
{
    private const ALIASES = [
        'date' => ['date', 'transaction date', 'posted transactions date', 'posted date', 'posting date', 'booking date', 'value date', 'completed date'],
        'description' => ['description', 'details', 'narrative', 'transaction details', 'description1', 'memo', 'payee', 'reference', 'transaction description'],
        'description_extra' => ['description2', 'description3', 'transaction type'],
        'amount' => ['amount', 'value', 'transaction amount'],
        'debit' => ['debit', 'debit amount', 'money out', 'paid out', 'withdrawals', 'withdrawal'],
        'credit' => ['credit', 'credit amount', 'money in', 'paid in', 'deposits', 'deposit', 'lodgement'],
        'balance' => ['balance', 'running balance', 'account balance', 'closing balance'],
    ];

    private const DATE_FORMATS = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'd/m/y', 'd-m-y', 'd M Y', 'd-M-Y', 'd M y', 'Ymd'];

    public function parse(string $path): array
    {
        $text = (string) file_get_contents($path);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $delimiter = $this->detectDelimiter($lines);
        $records = array_map(fn ($line) => str_getcsv($line, $delimiter), $lines);

        [$headerIndex, $map, $headers] = $this->findHeader($records);
        if ($headerIndex === null) {
            return ['rows' => [], 'errors' => ['Could not find a header row with a date column, a description column and an amount (or debit/credit) column.']];
        }

        $rows = [];
        $errors = [];

        foreach (array_slice($records, $headerIndex + 1, null, true) as $i => $cells) {
            $lineNo = $i + 1;
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $get = fn (string $field) => isset($map[$field]) ? trim((string) ($cells[$map[$field]] ?? '')) : '';

            $date = $this->parseDate($get('date'));
            if ($date === null) {
                $errors[] = "Line $lineNo: unreadable date \"{$get('date')}\".";

                continue;
            }

            if (isset($map['amount'])) {
                $amount = $this->parseMoney($get('amount'));
            } else {
                $debit = $this->parseMoney($get('debit'));
                $credit = $this->parseMoney($get('credit'));
                $amount = ($debit === null && $credit === null) ? null : abs($credit ?? 0) - abs($debit ?? 0);
            }
            if ($amount === null) {
                $errors[] = "Line $lineNo: no readable amount.";

                continue;
            }

            $description = $get('description');
            foreach ($map['description_extra'] ?? [] as $col) {
                $extra = trim((string) ($cells[$col] ?? ''));
                if ($extra !== '') {
                    $description = trim($description.' '.$extra);
                }
            }

            $raw = [];
            foreach ($headers as $col => $name) {
                $raw[$name !== '' ? $name : "column_$col"] = (string) ($cells[$col] ?? '');
            }

            $rows[] = new ParsedRow(
                $date,
                $description !== '' ? mb_substr($description, 0, 500) : '(no description)',
                $amount,
                isset($map['balance']) ? $this->parseMoney($get('balance')) : null,
                $raw,
            );
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function detectDelimiter(array $lines): string
    {
        $sample = implode("\n", array_slice($lines, 0, 10));
        $best = ',';
        $bestCount = 0;
        foreach ([',', ';', "\t", '|'] as $candidate) {
            if (($count = substr_count($sample, $candidate)) > $bestCount) {
                [$best, $bestCount] = [$candidate, $count];
            }
        }

        return $best;
    }

    /** Banks often put account details above the real header, so look in the first lines. */
    private function findHeader(array $records): array
    {
        foreach (array_slice($records, 0, 15, true) as $index => $cells) {
            $headers = array_map(fn ($c) => trim((string) $c), $cells);
            $normalised = array_map(fn ($c) => strtolower(preg_replace('/\s+/', ' ', trim($c, " \t\"'"))), $headers);

            $map = [];
            foreach (self::ALIASES as $field => $aliases) {
                foreach ($normalised as $col => $name) {
                    if (in_array($name, $aliases, true)) {
                        if ($field === 'description_extra') {
                            $map[$field][] = $col;
                        } elseif (! isset($map[$field])) {
                            $map[$field] = $col;
                        }
                    }
                }
            }

            $hasAmount = isset($map['amount']) || isset($map['debit']) || isset($map['credit']);
            if (isset($map['date'], $map['description']) && $hasAmount) {
                return [$index, $map, $headers];
            }
        }

        return [null, [], []];
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        foreach (self::DATE_FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $problems = DateTimeImmutable::getLastErrors();
            if ($date && (! $problems || ($problems['warning_count'] === 0 && $problems['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /** "1,234.56", "1.234,56", "(50.00)", "-€12", "12.00 DR" => integer cents. */
    private function parseMoney(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || ! preg_match('/\d/', $value)) {
            return null;
        }

        $negative = str_starts_with($value, '-') || str_ends_with($value, '-')
            || (str_starts_with($value, '(') && str_ends_with($value, ')'))
            || (bool) preg_match('/\bDR\b/i', $value);

        $digits = preg_replace('/[^0-9.,]/', '', $value);
        $lastComma = strrpos($digits, ',');
        $lastDot = strrpos($digits, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot) && strlen($digits) - $lastComma - 1 <= 2) {
            // decimal comma: 1.234,56
            $digits = str_replace(',', '.', str_replace('.', '', $digits));
        } else {
            $digits = str_replace(',', '', $digits);
        }

        if (! is_numeric($digits)) {
            return null;
        }

        $cents = (int) round(((float) $digits) * 100);

        return $negative ? -$cents : $cents;
    }
}
