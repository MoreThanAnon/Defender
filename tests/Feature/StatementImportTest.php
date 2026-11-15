<?php

namespace Tests\Feature;

use App\Models\User;
use BusinessCore\Models\BankTransaction;
use BusinessCore\Services\BusinessLifecycle;
use BusinessCore\Services\CsvStatementParser;
use BusinessCore\Services\StatementImporter;
use BusinessCore\Support\ImportException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StatementImportTest extends TestCase
{
    use RefreshDatabase;

    private function file(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'stmt');
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_parser_reads_common_layouts(): void
    {
        $parser = new CsvStatementParser();

        // Signed amount column, semicolons, decimal commas, newest first, ISO dates.
        $result = $parser->parse($this->file("Booking date;Narrative;Amount;Balance\n2026-10-03;Card payment;-1.234,56;8.765,44\n2026-10-01;Lodgement;10.000,00;10.000,00\n"));
        $this->assertSame([], $result['errors']);
        $this->assertSame(-123456, $result['rows'][0]->amountCents);
        $this->assertSame(876544, $result['rows'][0]->balanceCents);
        $this->assertSame('2026-10-01', $result['rows'][1]->date);

        // Day-first dates, brackets for money out, no balance column.
        $result = $parser->parse($this->file("Date,Description,Amount\n05/11/2026,Fee,(4.50)\n06/11/2026,Refund,€12\n"));
        $this->assertSame('2026-11-05', $result['rows'][0]->date);
        $this->assertSame(-450, $result['rows'][0]->amountCents);
        $this->assertSame(1200, $result['rows'][1]->amountCents);
        $this->assertNull($result['rows'][0]->balanceCents);

        // Unreadable lines are reported, with line numbers.
        $result = $parser->parse($this->file("Date,Description,Amount\nnot-a-date,Fee,1\n01/01/2026,Fee,abc\n"));
        $this->assertCount(2, $result['errors']);

        $this->assertNotEmpty($parser->parse($this->file("foo,bar\n1,2\n"))['errors']);
    }

    public function test_import_is_all_or_nothing_and_never_duplicates(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $business = app(BusinessLifecycle::class)->create(['name' => 'Alpha', 'type' => 'business'], $user);
        $importer = app(StatementImporter::class);

        // A bad line rejects the whole file.
        try {
            $importer->import($business, $this->file("Date,Description,Amount\n01/10/2026,Ok,5\nxx,Bad,5\n"), 'bad.csv', $user);
            $this->fail('Bad file was imported.');
        } catch (ImportException $e) {
            $this->assertNotEmpty($e->problems);
        }
        $this->assertSame(0, BankTransaction::count());

        $october = base_path('tests/Fixtures/statement-october.csv');
        $import = $importer->import($business, $october, 'oct.csv', $user);
        $this->assertSame(6, $import->imported_count);
        $this->assertSame(0, $import->balance_warnings);
        $this->assertSame('21400.00', $import->closing_balance);
        $this->assertSame(hash_file('sha256', $october), $import->sha256);
        Storage::disk('local')->assertExists($import->path);
        $this->assertSame('20,000.00', BankTransaction::orderBy('row_number')->first()->raw['Credit']);

        // Same file again is refused outright.
        $this->expectException(ImportException::class);
        $importer->import($business, $october, 'oct-again.csv', $user);
    }

    public function test_identical_same_day_payments_are_both_kept_and_balance_gaps_are_flagged(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $business = app(BusinessLifecycle::class)->create(['name' => 'Alpha', 'type' => 'business'], $user);

        $import = app(StatementImporter::class)->import($business, $this->file(
            "Date,Details,Debit,Credit,Balance\n01/10/2026,Coffee,3.00,,97.00\n01/10/2026,Coffee,3.00,,94.00\n02/10/2026,Mystery,,10.00,150.00\n"
        ), 'twins.csv', $user);

        $this->assertSame(3, $import->imported_count);
        $this->assertSame(1, $import->balance_warnings);

        // With no balance column the dashboard falls back to opening balance + lines, and says so.
        $other = app(BusinessLifecycle::class)->create(['name' => 'Beta', 'type' => 'house'], $user);
        $other->update(['opening_balance' => 100, 'opening_balance_date' => '2026-09-30']);
        app(StatementImporter::class)->import($other, $this->file("Date,Description,Amount\n05/10/2026,Rent,-40.00\n"), 'nobal.csv', $user);
        $snapshot = $other->fresh()->balanceSnapshot();
        $this->assertSame('60.00', $snapshot['amount']);
        $this->assertSame('calculated', $snapshot['source']);
    }
}
