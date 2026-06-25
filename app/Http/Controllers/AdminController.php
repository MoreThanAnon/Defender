<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\DecidimGovernanceSpace;
use App\Models\DecidimGovernanceSync;
use App\Models\AssetDocument;
use App\Models\AssetOperationsChecklist;
use App\Models\Offering;
use App\Models\Property;
use App\Models\User;
use App\Services\AssetHealthService;
use App\Services\Decidim\DecidimGovernanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
                'label' => 'Asset Operations',
                'description' => 'Track cash, bills, tasks, meetings, proposals and legal-review items for any funded asset.',
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

    public function operations(AssetHealthService $health)
    {
        $offerings = Offering::with(['property', 'decidimGovernanceSpace', 'assetOperationsChecklist'])
            ->withCount('decidimGovernanceSyncs')
            ->where('status', 'SETTLED')
            ->orderByDesc('updated_at')
            ->paginate(15);

        $healthAlerts = $offerings->getCollection()
            ->mapWithKeys(fn (Offering $offering) => [$offering->id => $health->alertsFor($offering)]);

        $summary = [
            'settled' => Offering::where('status', 'SETTLED')->count(),
            'checklists' => AssetOperationsChecklist::count(),
            'reviewed' => AssetOperationsChecklist::whereNotNull('reviewed_at')->count(),
            'with_health_alerts' => $healthAlerts->filter(fn (array $alerts) => $alerts !== [])->count(),
            'uploaded_documents' => AssetDocument::count(),
            'documents_needing_review' => AssetDocument::where('review_status', 'needs_review')->count(),
            'documents_reviewed' => AssetDocument::where('review_status', 'reviewed')->count(),
            'documents_changes_needed' => AssetDocument::where('review_status', 'changes_needed')->count(),
            'legal_pending' => AssetOperationsChecklist::where(function ($query) {
                $query->whereNull('legal_review')
                    ->orWhereJsonContains('legal_review->solicitor_review', false)
                    ->orWhereJsonContains('legal_review->central_bank_review', false)
                    ->orWhereJsonContains('legal_review->company_law_review', false)
                    ->orWhereJsonContains('legal_review->tax_review', false)
                    ->orWhereJsonContains('legal_review->property_structure_review', false);
            })->count(),
        ];

        return view('admin.operations.index', compact('offerings', 'summary', 'healthAlerts'));
    }

    public function showOperations(Offering $offering)
    {
        abort_unless($offering->status === 'SETTLED', 404);

        $checklist = AssetOperationsChecklist::firstOrCreate(
            ['offering_id' => $offering->id],
            [
                'property_id' => $offering->property_id,
                'bills_due' => [],
                'maintenance_tasks' => [],
                'required_documents' => $this->emptyAssetDocumentChecklist(),
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

        $offering->load(['property', 'decidimGovernanceSpace', 'equityPositions', 'loanNotes', 'assetDocuments.uploader']);
        $assetDocumentItems = $this->assetDocumentChecklistItems();

        return view('admin.operations.show', compact('offering', 'checklist', 'assetDocumentItems'));
    }

    public function updateOperations(Request $request, Offering $offering)
    {
        abort_unless($offering->status === 'SETTLED', 404);

        $data = $request->validate([
            'bank_balance' => ['nullable', 'numeric', 'min:0'],
            'bills_due_text' => ['nullable', 'string'],
            'maintenance_tasks_text' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string'],
            'required_documents' => ['array'],
            'meeting_status' => ['array'],
            'proposal_status' => ['array'],
            'legal_review' => ['array'],
        ]);

        $checklist = AssetOperationsChecklist::firstOrCreate(
            ['offering_id' => $offering->id],
            ['property_id' => $offering->property_id]
        );

        $checklist->update([
            'property_id' => $offering->property_id,
            'bank_balance' => $data['bank_balance'] ?? null,
            'bills_due' => $this->linesToChecklist($data['bills_due_text'] ?? ''),
            'maintenance_tasks' => $this->linesToChecklist($data['maintenance_tasks_text'] ?? ''),
            'required_documents' => $this->booleanMap($data['required_documents'] ?? []),
            'meeting_status' => $this->booleanMap($data['meeting_status'] ?? []),
            'proposal_status' => $this->booleanMap($data['proposal_status'] ?? []),
            'legal_review' => $this->booleanMap($data['legal_review'] ?? []),
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.operations.show', $offering)
            ->with('success', 'Asset operations checklist updated.');
    }

    public function uploadAssetDocument(Request $request, Offering $offering)
    {
        abort_unless($offering->status === 'SETTLED', 404);

        $documentItems = $this->assetDocumentChecklistItems();

        $data = $request->validate([
            'document_key' => ['required', 'string', Rule::in(array_keys($documentItems))],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,txt'],
        ]);

        $file = $request->file('document');
        $path = $file->store("asset-documents/offering-{$offering->id}", 'local');

        AssetDocument::create([
            'offering_id' => $offering->id,
            'property_id' => $offering->property_id,
            'uploaded_by' => Auth::id(),
            'document_key' => $data['document_key'],
            'title' => $data['title'] ?: $documentItems[$data['document_key']]['label'],
            'notes' => $data['notes'] ?? null,
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?: 0,
            'review_status' => 'needs_review',
        ]);

        $checklist = AssetOperationsChecklist::firstOrCreate(
            ['offering_id' => $offering->id],
            ['property_id' => $offering->property_id]
        );

        $requiredDocuments = $checklist->required_documents ?? $this->emptyAssetDocumentChecklist();
        $requiredDocuments[$data['document_key']] = true;
        $checklist->update([
            'property_id' => $offering->property_id,
            'required_documents' => $requiredDocuments,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.operations.show', $offering)
            ->with('success', 'Asset document uploaded. It still needs human review.');
    }

    public function downloadAssetDocument(Offering $offering, AssetDocument $document)
    {
        abort_unless($offering->status === 'SETTLED' && (int) $document->offering_id === (int) $offering->id, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_filename);
    }

    public function updateAssetDocumentReview(Request $request, Offering $offering, AssetDocument $document)
    {
        abort_unless($offering->status === 'SETTLED' && (int) $document->offering_id === (int) $offering->id, 404);

        $data = $request->validate([
            'review_status' => ['required', 'string', Rule::in(['needs_review', 'reviewed', 'changes_needed'])],
            'notes' => ['nullable', 'string'],
            'visible_to_investors' => ['nullable', 'boolean'],
            'publication_notes' => ['nullable', 'string'],
        ]);

        $visibleToInvestors = $data['review_status'] === 'reviewed'
            ? $request->boolean('visible_to_investors')
            : false;

        $document->update([
            'review_status' => $data['review_status'],
            'notes' => $data['notes'] ?? $document->notes,
            'reviewed_at' => $data['review_status'] === 'reviewed' ? now() : null,
            'visible_to_investors' => $visibleToInvestors,
            'published_at' => $visibleToInvestors ? ($document->published_at ?? now()) : null,
            'publication_notes' => $data['publication_notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.operations.show', $offering)
            ->with('success', 'Asset document review status updated.');
    }

    public function deleteAssetDocument(Offering $offering, AssetDocument $document)
    {
        abort_unless($offering->status === 'SETTLED' && (int) $document->offering_id === (int) $offering->id, 404);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return redirect()
            ->route('admin.operations.show', $offering)
            ->with('success', 'Asset document deleted.');
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

    private function emptyAssetDocumentChecklist(): array
    {
        return collect(array_keys($this->assetDocumentChecklistItems()))
            ->mapWithKeys(fn (string $key) => [$key => false])
            ->all();
    }

    /**
     * @return array<string,array{label:string,description:string}>
     */
    private function assetDocumentChecklistItems(): array
    {
        return [
            'asset_identity' => [
                'label' => 'Asset identity and description',
                'description' => 'Plain-English description of what is being bought or funded, such as a rental house, shop unit, land parcel, business, vehicle, equipment, or other asset.',
            ],
            'ownership_or_title' => [
                'label' => 'Ownership / title / seller authority',
                'description' => 'Evidence that the seller or vendor can sell it, for example title documents, land registry details, company ownership records, asset register entry, or sale authority.',
            ],
            'valuation_or_price_support' => [
                'label' => 'Valuation or price support',
                'description' => 'Independent valuation, survey, comparables, quote, invoice, book value, broker note, or other evidence explaining why the purchase price makes sense.',
            ],
            'acquisition_agreement' => [
                'label' => 'Acquisition agreement / heads of terms',
                'description' => 'Draft or signed contract, heads of terms, purchase order, lease assignment, share purchase agreement, or equivalent deal document.',
            ],
            'income_or_use_plan' => [
                'label' => 'Income or use plan',
                'description' => 'How the asset is expected to make money or create value, for example rent assumptions, shop trading plan, land development route, resale plan, or operating forecast.',
            ],
            'costs_and_liabilities' => [
                'label' => 'Costs, debts, and liabilities',
                'description' => 'Known costs such as rates, service charges, insurance, utilities, repairs, debt, taxes, wages, supplier liabilities, or other obligations attached to the asset.',
            ],
            'insurance_and_risk' => [
                'label' => 'Insurance and risk review',
                'description' => 'Insurance quote/policy and risk notes for damage, vacancy, tenant default, planning risk, trading risk, market risk, or asset-specific operational risk.',
            ],
            'regulatory_permissions' => [
                'label' => 'Regulatory permissions / licences',
                'description' => 'Planning permission, fire certs, food/shop licences, tenancy compliance, environmental checks, crowdfunding permissions, or any other approval needed before use.',
            ],
            'governance_and_investor_rights' => [
                'label' => 'Governance and investor rights pack',
                'description' => 'Documents explaining who controls decisions, voting rights, exit rights, profit distribution, conflicts, investor updates, and how Decidim governance will be used.',
            ],
            'tax_and_structure_review' => [
                'label' => 'Tax and legal structure review',
                'description' => 'Notes for solicitor/accountant review covering SPV/company structure, tax treatment, ownership, investor rights, and Irish regulatory review. This is a reminder, not compliance approval.',
            ],
        ];
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
