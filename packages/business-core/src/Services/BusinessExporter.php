<?php

namespace BusinessCore\Services;

use BusinessCore\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Offboarding: everything a business has put in, as CSV files plus the original
 * statements and documents, in one zip. No lock-in.
 */
class BusinessExporter
{
    private const TABLES = [
        'business_memberships', 'business_assets', 'business_projects', 'business_suppliers', 'business_proposals',
        'business_payment_tasks', 'business_statement_imports', 'business_bank_transactions', 'business_documents',
        'business_tasks', 'business_report_signoffs', 'business_audit_events',
    ];

    /** @return string path of a temporary zip file; the caller deletes it after sending */
    public function export(Business $business): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required for exports.');
        }

        $path = tempnam(sys_get_temp_dir(), 'bizexport');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('business.csv', $this->csv([(array) DB::table('businesses')->where('id', $business->id)->first()]));

        foreach (self::TABLES as $table) {
            $rows = DB::table($table)->where('business_id', $business->id)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $zip->addFromString(str_replace('business_', '', $table).'.csv', $this->csv($rows));
        }

        $proposalIds = DB::table('business_proposals')->where('business_id', $business->id)->pluck('id');
        foreach (['business_proposal_votes' => 'proposal_votes', 'business_proposal_comments' => 'proposal_comments'] as $table => $name) {
            $rows = DB::table($table)->whereIn('proposal_id', $proposalIds)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $zip->addFromString($name.'.csv', $this->csv($rows));
        }

        $disk = Storage::disk(config('business-core.disk'));
        foreach (DB::table('business_statement_imports')->where('business_id', $business->id)->get() as $import) {
            if ($disk->exists($import->path)) {
                $zip->addFromString('statements/'.$import->id.'-'.$this->safe($import->original_name), $disk->get($import->path));
            }
        }
        foreach (DB::table('business_documents')->where('business_id', $business->id)->get() as $document) {
            if ($disk->exists($document->path)) {
                $zip->addFromString('documents/'.$document->id.'-'.$this->safe($document->original_name), $disk->get($document->path));
            }
        }

        $zip->addFromString('README.txt', "Export of {$business->name} generated ".now()->toDayDateTimeString().".\n"
            ."CSV files are UTF-8. bank_transactions.csv holds each bank line as imported (column \"raw\") plus your own categories.\n"
            ."Balances are snapshots taken from uploaded statements, not live bank balances.\n");

        $zip->close();

        return $path;
    }

    private function csv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        if ($rows) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                // Stop spreadsheet formula injection from user-entered text.
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) && ! is_numeric($v) ? "'".$v : $v, $row));
            }
        }
        rewind($out);

        return (string) stream_get_contents($out);
    }

    private function safe(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    }
}
