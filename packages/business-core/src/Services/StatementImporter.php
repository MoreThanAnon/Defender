<?php

namespace BusinessCore\Services;

use BusinessCore\Contracts\AuditRecorder;
use BusinessCore\Contracts\StatementParser;
use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\Business;
use BusinessCore\Models\StatementImport;
use BusinessCore\Support\ImportException;
use BusinessCore\Support\ParsedRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Imports an uploaded bank statement. All-or-nothing: a file with an unreadable
 * line is rejected whole. The same file cannot be imported twice, and lines that
 * overlap an earlier statement are skipped, not duplicated.
 */
class StatementImporter
{
    public function __construct(private StatementParser $parser, private AuditRecorder $audit)
    {
    }

    public function import(Business $business, string $path, string $originalName, mixed $user = null): StatementImport
    {
        $hash = hash_file('sha256', $path);

        $already = StatementImport::withoutGlobalScope('business')
            ->where('business_id', $business->id)->where('sha256', $hash)->first();
        if ($already) {
            throw new ImportException("This exact file was already imported on {$already->created_at->format('j M Y')}.");
        }

        $parsed = $this->parser->parse($path);
        if ($parsed['errors']) {
            throw new ImportException('The file was not imported because some lines could not be read.', array_slice($parsed['errors'], 0, 20));
        }
        if (! $parsed['rows']) {
            throw new ImportException('The file contains no transactions.');
        }

        // Work oldest-first whatever order the bank exported in.
        $rows = $parsed['rows'];
        if ($rows[0]->date > $rows[count($rows) - 1]->date) {
            $rows = array_reverse($rows);
        }

        $disk = config('business-core.disk');
        $stored = "business-core/{$business->id}/statements/{$hash}.csv";

        return DB::transaction(function () use ($business, $rows, $hash, $stored, $disk, $path, $originalName, $user) {
            Storage::disk($disk)->put($stored, file_get_contents($path));

            $import = StatementImport::create([
                'business_id' => $business->id,
                'original_name' => mb_substr($originalName, 0, 191),
                'path' => $stored,
                'sha256' => $hash,
                'row_count' => count($rows),
                'period_start' => min(array_map(fn (ParsedRow $r) => $r->date, $rows)),
                'period_end' => max(array_map(fn (ParsedRow $r) => $r->date, $rows)),
                'imported_by' => $user?->getKey(),
            ]);

            $existing = BankTransaction::withoutGlobalScope('business')
                ->where('business_id', $business->id)
                ->whereBetween('booked_on', [$import->period_start, $import->period_end])
                ->pluck('fingerprint')->flip();

            $seen = [];
            $imported = 0;
            $warnings = 0;
            $previous = null;
            $closing = null;

            foreach ($rows as $index => $row) {
                // Two genuinely identical payments on one day get different fingerprints (1st, 2nd, ...).
                $key = $row->date.'|'.$row->amountCents.'|'.mb_strtolower(preg_replace('/\s+/', ' ', $row->description)).'|'.($row->balanceCents ?? '');
                $seen[$key] = ($seen[$key] ?? 0) + 1;
                $fingerprint = hash('sha256', $key.'|'.$seen[$key]);

                if ($previous && $previous->balanceCents !== null && $row->balanceCents !== null
                    && $previous->balanceCents + $row->amountCents !== $row->balanceCents) {
                    $warnings++;
                }
                $previous = $row;
                $closing = $row->balanceCents ?? $closing;

                if (isset($existing[$fingerprint])) {
                    continue;
                }

                BankTransaction::create([
                    'business_id' => $business->id,
                    'statement_import_id' => $import->id,
                    'row_number' => $index + 1,
                    'raw' => $row->raw,
                    'fingerprint' => $fingerprint,
                    'booked_on' => $row->date,
                    'description' => $row->description,
                    'amount' => self::money($row->amountCents),
                    'balance_after' => $row->balanceCents === null ? null : self::money($row->balanceCents),
                ]);
                $imported++;
            }

            $import->update([
                'imported_count' => $imported,
                'duplicate_count' => count($rows) - $imported,
                'balance_warnings' => $warnings,
                'closing_balance' => $closing === null ? null : self::money($closing),
            ]);

            $this->audit->record('statement.imported', $import, [
                'file' => $originalName, 'sha256' => $hash, 'imported' => $imported, 'duplicates' => count($rows) - $imported,
            ], $business);

            return $import;
        });
    }

    private static function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
