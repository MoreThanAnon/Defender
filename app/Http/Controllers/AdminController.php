<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Property;
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
}
