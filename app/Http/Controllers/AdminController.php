<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\DecidimGovernanceSpace;
use App\Models\DecidimGovernanceSync;
use App\Models\HotelOperationsChecklist;
use App\Models\Offering;
use App\Models\Property;
use App\Models\User;
use App\Services\Decidim\DecidimGovernanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalEquity = Investment::where('type', 'equity')->sum('amount');
        $totalLoan = Investment::where('type', 'loan')->sum('amount');
        $totalInvested = $totalEquity + $totalLoan;

        $activeLoans = Investment::where('type', 'loan')->where('status', 'active')->sum('amount');
        $paidLoans = Investment::where('type', 'loan')->where('status', 'paid')->sum('amount');
        $inactiveLoans = Investment::where('type', 'loan')->where('status', 'inactive')->sum('amount');
        $avgProfit = 0;
        $recentProperties = Property::orderByDesc('id')->take(5)->get();
        $propertyStatuses = DB::table('properties_status')->pluck('status', 'id');
        $adminLinks = [
            [
                'label' => 'Manage Properties',
                'description' => 'View all properties and choose one to edit.',
                'url' => route('admin.properties.index'),
                'class' => 'primary',
            ],
            [
                'label' => 'Create Property',
                'description' => 'Add a new property to the platform.',
                'url' => route('property.create'),
                'class' => 'success',
            ],
            [
                'label' => 'Public Property List',
                'description' => 'Review the property listing seen by users.',
                'url' => route('properties.index'),
                'class' => 'secondary',
            ],
            [
                'label' => 'Act as User',
                'description' => 'Switch into a user account by their user ID.',
                'url' => route('admin.users.index'),
                'class' => 'warning',
            ],
            [
                'label' => 'Decidim Integration',
                'description' => 'Check governance sync status, Assembly URLs, investor access and retries.',
                'url' => route('admin.decidim.index'),
                'class' => 'info',
            ],
            [
                'label' => 'Hotel Operations',
                'description' => 'Track bank balance, bills, maintenance, meetings, proposals and legal-review items.',
                'url' => route('admin.operations.index'),
                'class' => 'dark',
            ],
        ];

        return view('admin.dashboard', compact(
            'totalEquity',
            'totalLoan',
            'totalInvested',
            'activeLoans',
            'paidLoans',
            'inactiveLoans',
            'avgProfit',
            'recentProperties',
            'propertyStatuses',
            'adminLinks'
        ));
    }

    public function properties()
    {
        $properties = Property::all();
        $propertyStatuses = DB::table('properties_status')->pluck('status', 'id');

        return view('admin.properties', compact('properties', 'propertyStatuses'));
    }

    public function users()
    {
        $users = User::orderBy('id')->paginate(25);

        return view('admin.users', compact('users'));
    }

    public function decidim()
    {
        $spaces = DecidimGovernanceSpace::with(['offering.property', 'property'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        $syncCounts = DecidimGovernanceSync::query()
            ->select('offering_id', DB::raw('count(*) as total'))
            ->groupBy('offering_id')
            ->pluck('total', 'offering_id');

        $offeringsWithoutSpaces = Offering::with('property')
            ->where('status', 'SETTLED')
            ->whereDoesntHave('decidimGovernanceSpace')
            ->orderByDesc('updated_at')
            ->take(15)
            ->get();

        $summary = [
            'spaces_total' => DecidimGovernanceSpace::count(),
            'created' => DecidimGovernanceSpace::where('status', 'created')->count(),
            'failed' => DecidimGovernanceSpace::where('status', 'failed')->count(),
            'pending' => DecidimGovernanceSpace::where('status', 'pending')->count(),
            'syncing' => DecidimGovernanceSpace::where('status', 'syncing')->count(),
            'synced_roles' => DecidimGovernanceSync::count(),
            'settled_without_space' => Offering::where('status', 'SETTLED')->whereDoesntHave('decidimGovernanceSpace')->count(),
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
        ];

        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('failed_at')->take(10)->get()
            : collect();

        return view('admin.decidim', compact('spaces', 'syncCounts', 'offeringsWithoutSpaces', 'summary', 'failedJobs'));
    }

    public function retryDecidim(Offering $offering, DecidimGovernanceService $governance)
    {
        try {
            $space = $governance->createGovernanceForSettledOffering($offering->fresh(['property', 'equityPositions', 'loanNotes']));

            return redirect()
                ->route('admin.decidim.index')
                ->with('success', "Decidim sync completed for offering #{$offering->id}. Status: {$space->status}.");
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.decidim.index')
                ->with('error', "Decidim sync failed for offering #{$offering->id}: {$e->getMessage()}");
        }
    }

    public function operations()
    {
        $offerings = Offering::with(['property', 'decidimGovernanceSpace', 'hotelOperationsChecklist'])
            ->where('status', 'SETTLED')
            ->orderByDesc('updated_at')
            ->paginate(15);

        $summary = [
            'settled' => Offering::where('status', 'SETTLED')->count(),
            'checklists' => HotelOperationsChecklist::count(),
            'reviewed' => HotelOperationsChecklist::whereNotNull('reviewed_at')->count(),
            'legal_pending' => HotelOperationsChecklist::where(function ($query) {
                $query->whereNull('legal_review')
                    ->orWhereJsonContains('legal_review->solicitor_review', false)
                    ->orWhereJsonContains('legal_review->central_bank_review', false)
                    ->orWhereJsonContains('legal_review->company_law_review', false)
                    ->orWhereJsonContains('legal_review->tax_review', false)
                    ->orWhereJsonContains('legal_review->property_structure_review', false);
            })->count(),
        ];

        return view('admin.operations.index', compact('offerings', 'summary'));
    }

    public function showOperations(Offering $offering)
    {
        abort_unless($offering->status === 'SETTLED', 404);

        $checklist = HotelOperationsChecklist::firstOrCreate(
            ['offering_id' => $offering->id],
            [
                'property_id' => $offering->property_id,
                'bills_due' => [],
                'maintenance_tasks' => [],
                'meeting_status' => [
                    'first_meeting_scheduled' => false,
                    'agenda_published' => false,
                    'minutes_published' => false,
                ],
                'proposal_status' => [
                    'operating_plan_proposed' => false,
                    'maintenance_budget_proposed' => false,
                    'bank_reporting_proposed' => false,
                ],
                'legal_review' => [
                    'solicitor_review' => false,
                    'central_bank_review' => false,
                    'company_law_review' => false,
                    'investor_rights_review' => false,
                    'tax_review' => false,
                    'property_structure_review' => false,
                    'data_protection_review' => false,
                ],
            ]
        );

        $offering->load(['property', 'decidimGovernanceSpace', 'equityPositions', 'loanNotes']);

        return view('admin.operations.show', compact('offering', 'checklist'));
    }

    public function updateOperations(Request $request, Offering $offering)
    {
        abort_unless($offering->status === 'SETTLED', 404);

        $data = $request->validate([
            'bank_balance' => ['nullable', 'numeric', 'min:0'],
            'bills_due_text' => ['nullable', 'string'],
            'maintenance_tasks_text' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string'],
            'meeting_status' => ['array'],
            'proposal_status' => ['array'],
            'legal_review' => ['array'],
        ]);

        $checklist = HotelOperationsChecklist::firstOrCreate(
            ['offering_id' => $offering->id],
            ['property_id' => $offering->property_id]
        );

        $checklist->update([
            'property_id' => $offering->property_id,
            'bank_balance' => $data['bank_balance'] ?? null,
            'bills_due' => $this->linesToChecklist($data['bills_due_text'] ?? ''),
            'maintenance_tasks' => $this->linesToChecklist($data['maintenance_tasks_text'] ?? ''),
            'meeting_status' => $this->booleanMap($data['meeting_status'] ?? []),
            'proposal_status' => $this->booleanMap($data['proposal_status'] ?? []),
            'legal_review' => $this->booleanMap($data['legal_review'] ?? []),
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.operations.show', $offering)
            ->with('success', 'Hotel operations checklist updated.');
    }

    private function linesToChecklist(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(fn (string $line) => ['label' => $line, 'done' => false])
            ->values()
            ->all();
    }

    private function booleanMap(array $values): array
    {
        return collect($values)->map(fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN))->all();
    }

    public function impersonate(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $impersonatorId = $request->session()->get('impersonator_id', Auth::id());

        if ((int) $data['user_id'] === (int) $impersonatorId) {
            return back()->with('error', 'You are already using your own account.');
        }

        Auth::loginUsingId($data['user_id']);
        $request->session()->put('impersonator_id', $impersonatorId);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'You are now acting as user ID '.$data['user_id'].'.');
    }

    public function stopImpersonating(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        $impersonator = $impersonatorId ? User::find($impersonatorId) : null;

        if (! $impersonator) {
            return redirect()->route('home')->with('error', 'No admin session was found to return to.');
        }

        Auth::login($impersonator);
        $request->session()->regenerate();

        $route = $impersonator->hasRole('admin') ? 'admin.dashboard' : 'home';

        return redirect()->route($route)->with('success', 'You are back in your admin account.');
    }
}
