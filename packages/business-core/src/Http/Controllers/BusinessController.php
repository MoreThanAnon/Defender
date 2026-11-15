<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\Business;
use BusinessCore\Models\PaymentTask;
use BusinessCore\Models\Project;
use BusinessCore\Models\Proposal;
use BusinessCore\Models\Task;
use BusinessCore\Services\BusinessLifecycle;
use BusinessCore\Support\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessController extends Controller
{
    /** The businesses this person belongs to. */
    public function index(Request $request)
    {
        $memberships = $request->user()->businessMemberships()->withoutGlobalScope('business')
            ->with('business')->get()->sortBy('business.name');

        return $this->view('businesses.index', ['memberships' => $memberships]);
    }

    public function create()
    {
        return $this->view('businesses.create');
    }

    public function store(Request $request, BusinessLifecycle $lifecycle)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(config('business-core.types')))],
            'legal_name' => ['nullable', 'string', 'max:191'],
            'registration_number' => ['nullable', 'string', 'max:60'],
        ]);

        $business = $lifecycle->create($data, $request->user());

        return redirect()->route('bc.show', $business)
            ->with('status', 'Business created in storage. Set it up and try it out; it is not linked to a live company yet.');
    }

    /** Home dashboard for one business. */
    public function show()
    {
        $this->allow('view');
        $business = $this->biz();
        $canSeeMoney = $this->current()->can('reports');
        $since = now()->subDays(30)->toDateString();

        return $this->view('businesses.show', [
            'balance' => $canSeeMoney ? $business->balanceSnapshot() : null,
            'moneyIn' => $canSeeMoney ? (float) BankTransaction::where('booked_on', '>=', $since)->where('amount', '>', 0)->sum('amount') : null,
            'moneyOut' => $canSeeMoney ? -1 * (float) BankTransaction::where('booked_on', '>=', $since)->where('amount', '<', 0)->sum('amount') : null,
            'unreviewed' => $canSeeMoney ? BankTransaction::whereIn('status', ['unreviewed', 'flagged'])->count() : null,
            'openProposals' => Proposal::where('status', 'open')->latest()->limit(5)->get(),
            'openProposalCount' => Proposal::where('status', 'open')->count(),
            'awaitingStatement' => PaymentTask::where('status', 'sent')->count(),
            'toPay' => PaymentTask::where('status', 'prepared')->count(),
            'overdueTasks' => Task::where('status', '!=', 'done')->whereDate('due_date', '<', today())->orderBy('due_date')->get(),
            'projects' => Project::whereIn('status', ['planned', 'active', 'on_hold'])->orderBy('name')->get(),
        ]);
    }

    public function edit()
    {
        $this->allow('manage');

        return $this->view('businesses.settings', ['canGoLive' => $this->canChangeLifecycle()]);
    }

    public function update(Request $request)
    {
        $this->allow('manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(config('business-core.types')))],
            'bank_url' => ['nullable', 'url', 'max:191'],
            'opening_balance' => ['nullable', 'numeric', 'between:-99999999,99999999'],
            'opening_balance_date' => ['nullable', 'date', 'required_with:opening_balance'],
            'quorum_percent' => ['required', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->biz()->update($data);
        $this->audit('business.updated', $this->biz(), ['fields' => array_keys($data)]);

        return back()->with('status', 'Settings saved.');
    }

    /** Link a stored business to the real company and bank it now represents. */
    public function goLive(Request $request, BusinessLifecycle $lifecycle)
    {
        abort_unless($this->canChangeLifecycle(), 403);

        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:191'],
            'registration_number' => ['nullable', 'string', 'max:60'],
            'bank_name' => ['required', 'string', 'max:191'],
            'bank_url' => ['nullable', 'url', 'max:191'],
            'bank_account_label' => ['nullable', 'string', 'max:60'],
            'confirmed' => ['accepted'],
        ], ['confirmed.accepted' => 'Confirm that you have checked the company and its bank account.']);

        $clear = $request->boolean('clear_sandbox');
        unset($data['confirmed']);

        $lifecycle->goLive($this->biz(), $data, $request->user(), $clear);

        return redirect()->route('bc.show')->with('status', 'This business is now live.');
    }

    public function setStatus(Request $request, BusinessLifecycle $lifecycle)
    {
        abort_unless($this->canChangeLifecycle(), 403);

        $data = $request->validate(['status' => ['required', Rule::in([Business::STORED, Business::ARCHIVED])]]);
        $lifecycle->setStatus($this->biz(), $data['status']);

        return redirect()->route('bc.show')->with('status', 'Status changed.');
    }

    private function canChangeLifecycle(): bool
    {
        if (CurrentBusiness::isPlatformAdmin(request()->user())) {
            return true;
        }

        return config('business-core.go_live') === 'director' && $this->current()->role() === 'director';
    }
}
