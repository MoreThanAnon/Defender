<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
