<?php

namespace BusinessCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One independently owned business: a house, a company, a piece of land.
 * It is the tenant; everything else hangs off it.
 */
class Business extends Model
{
    public const STORED = 'stored';     // sandbox / on the shelf, not tied to a real company yet
    public const LIVE = 'live';         // linked to a verified real company and its own bank
    public const ARCHIVED = 'archived'; // read-only

    protected $table = 'businesses';

    protected $guarded = [];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'went_live_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function makeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        if (in_array($base, ['new', 'admin', 'invitations'], true)) {
            $base .= '-biz';
        }
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    public function isLive(): bool
    {
        return $this->status === self::LIVE;
    }

    public function isStored(): bool
    {
        return $this->status === self::STORED;
    }

    public function typeLabel(): string
    {
        return config('business-core.types.'.$this->type, ucfirst((string) $this->type));
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function directors()
    {
        return $this->memberships()->where('role', 'director');
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function imports()
    {
        return $this->hasMany(StatementImport::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function paymentTasks()
    {
        return $this->hasMany(PaymentTask::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * The best balance figure we can honestly show, with where it came from.
     * It is always a snapshot as of a date, never "live available cash".
     *
     * source: statement   = balance printed on the latest imported bank line
     *         calculated  = entered opening balance plus imported transactions
     *         opening     = entered opening balance only
     *
     * @return array{amount: ?string, as_of: ?Carbon, source: ?string, stale: bool}
     */
    public function balanceSnapshot(): array
    {
        $latest = $this->transactions()
            ->orderByDesc('booked_on')->orderByDesc('statement_import_id')->orderByDesc('row_number')
            ->first();

        $amount = null;
        $asOf = null;
        $source = null;

        if ($latest && $latest->balance_after !== null) {
            [$amount, $asOf, $source] = [$latest->balance_after, $latest->booked_on, 'statement'];
        } elseif ($this->opening_balance !== null) {
            $since = $this->transactions()
                ->when($this->opening_balance_date, fn ($q) => $q->where('booked_on', '>', $this->opening_balance_date))
                ->sum('amount');
            $amount = number_format((float) $this->opening_balance + (float) $since, 2, '.', '');
            $asOf = $latest?->booked_on ?? $this->opening_balance_date;
            $source = $latest ? 'calculated' : 'opening';
        }

        $stale = $asOf !== null && $asOf->lt(now()->subDays((int) config('business-core.stale_import_days', 35))->startOfDay());

        return ['amount' => $amount, 'as_of' => $asOf, 'source' => $source, 'stale' => $stale];
    }
}
