<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\Business;
use BusinessCore\Services\BusinessLifecycle;
use BusinessCore\Support\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Platform operator's shelf: every business, grouped by storage / live / archived. */
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless(CurrentBusiness::isPlatformAdmin($request->user()), 403);

            return $next($request);
        });
    }

    public function index()
    {
        $businesses = Business::withCount('memberships')->with('directors.user')->orderBy('name')->get()->groupBy('status');

        return $this->view('admin.index', ['groups' => $businesses]);
    }

    /** Put a new business on the shelf. The operator is not made a member unless asked. */
    public function store(Request $request, BusinessLifecycle $lifecycle)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(config('business-core.types')))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $business = $lifecycle->create($data, $request->user(), $request->boolean('join_as_director'));

        return back()->with('status', "\"{$business->name}\" is in storage.");
    }

    /** Hand a business to its real owner by making an existing account a director. */
    public function assignDirector(Request $request, Business $business, BusinessLifecycle $lifecycle)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $userModel = config('business-core.user_model');
        $user = $userModel::where('email', $data['email'])->first();
        if (! $user) {
            return back()->withErrors(['email' => 'No account with that email. Ask them to register first, or invite them from the People screen.']);
        }

        $lifecycle->assignDirector($business, $user);

        return back()->with('status', CurrentBusiness::displayName($user)." is now a director of {$business->name}.");
    }
}
