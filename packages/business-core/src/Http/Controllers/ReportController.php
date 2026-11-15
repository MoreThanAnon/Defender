<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\AuditEvent;
use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\ReportSignoff;
use BusinessCore\Services\BusinessExporter;
use BusinessCore\Services\MonthlyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index()
    {
        $this->allow('reports');

        // Months that have bank data, newest first, plus the current month.
        $months = BankTransaction::orderByDesc('booked_on')->pluck('booked_on')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))->push(now()->format('Y-m'))->unique()->sortDesc()->values();

        return $this->view('reports.index', [
            'months' => $months,
            'signoffs' => ReportSignoff::with('signer')->get()->keyBy('period'),
        ]);
    }

    public function monthly(string $period, MonthlyReport $report)
    {
        $this->allow('reports');

        return $this->view('reports.monthly', $report->build($this->biz(), $period));
    }

    public function monthlyCsv(string $period, MonthlyReport $report)
    {
        $this->allow('reports');
        $data = $report->build($this->biz(), $period);
        $this->audit('report.exported', null, ['period' => $period, 'format' => 'csv']);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Date', 'Description', 'Amount', 'Balance after', 'Category', 'Project', 'Status', 'Payment task', 'Source file', 'Source line']);
        foreach ($data['transactions'] as $tx) {
            $description = preg_match('/^[=+\-@]/', $tx->description) ? "'".$tx->description : $tx->description;
            fputcsv($out, [
                $tx->booked_on->toDateString(), $description, $tx->amount, $tx->balance_after, $tx->category,
                $tx->project?->name, $tx->status, $tx->paymentTask?->reference, $tx->statementImport?->original_name, $tx->row_number,
            ]);
        }
        rewind($out);

        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($this->biz()->name)."-$period.csv\"",
        ]);
    }

    /** A director confirms they have reviewed the month. */
    public function signOff(Request $request, string $period)
    {
        $this->allow('decide');

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $signoff = ReportSignoff::updateOrCreate(
            ['business_id' => $this->biz()->id, 'period' => $period],
            ['signed_by' => $request->user()->getKey(), 'signed_at' => now(), 'note' => $data['note'] ?? null],
        );
        $this->audit('report.signed_off', $signoff, ['period' => $period]);

        return back()->with('status', "Report for $period signed off.");
    }

    public function auditLog()
    {
        $this->allow('reports');

        return $this->view('reports.audit', [
            'events' => AuditEvent::with('user')->latest('id')->paginate(50),
        ]);
    }

    /** Offboarding: download everything. */
    public function export(BusinessExporter $exporter)
    {
        $this->allow('export');

        $path = $exporter->export($this->biz());
        $this->audit('business.exported');

        return response()->download($path, Str::slug($this->biz()->name).'-export-'.now()->format('Y-m-d').'.zip')->deleteFileAfterSend();
    }
}
