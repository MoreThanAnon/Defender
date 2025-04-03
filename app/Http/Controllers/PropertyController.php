<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{

    public function __construct()
    {
        //$this->middleware(['auth', 'role:moderator,admin'])->only('store');
    }

    public function bid (Request $request, $id)
    {
        $request->validate([
            'bidAmount' => 'required|numeric|min:1',
        ]);
        //Check if user has sufficient funds
        $user = auth()->user();
        
        // Assuming you have a bids table to store bids
        DB::table('bids')->insert([
            'property_id' => $id,
            'user_id' => auth()->id(),
            'amount' => $request->bidAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Your bid has been placed successfully.');
    }

    public function show($id)
    {
        $property = Property::findOrFail($id);

        // Example values for collective fund and goal
        $collectiveFund = $property->collective_fund ?? 0; // Assuming 'collective_fund' is a column in the properties table
        $fundGoal = $property->fund_goal ?? 100000; // Assuming 'fund_goal' is a column in the properties table

        return view('property', [
            'id' => $property->id,
            'name' => $property->name,
            'price' => $property->price,
            'beds' => $property->beds,
            'baths' => $property->baths,
            'size' => $property->size,
            'type' => $property->type,
            'berRating' => $property->ber_rating,
            'address' => $property->address,
            'description' => $property->description,
            'images' => $property->images,
            'features' => $property->features,
            'saleType' => $property->sale_type,
            'stampDuty' => $property->stamp_duty,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'auctioneerName' => $property->auctioneer_name,
            'collectiveFund' => $collectiveFund,
            'fundGoal' => $fundGoal,
        ]);
    }
    /**
     * Show the form for creating a new property.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('property.create');
    }
    public function store(Request $request)
    {   
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'beds' => 'required|integer',
            'baths' => 'required|integer',
            'size' => 'required|numeric',
            'type' => 'required|string|max:255',
            'ber_rating' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'description' => 'nullable|string',
            'sale_type' => 'required|string|max:255',
            'stamp_duty' => 'nullable|numeric',
            'features' => 'nullable|array',
            'auctioneer_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'images' => 'nullable|array',
        ]);

        // Save the property to the database
        $property = Property::create($validatedData);

        // Redirect with success message
        return redirect()->route('property.show', $property->id)
                         ->with('success', 'Property uploaded successfully.');
    }

    public function edit($id)
    {
        $property = Property::find($id);

        if (!$property) {
            abort(404, 'Property not found');
        }

        return view('property_form', compact('property'));
    }

    public function update(Request $request, $id)
    {
        $property = Property::findOrFail($id);
        $property->update($request->all());
        if ($request->hasFile('images')) {
            // Handle image upload logic
        }
        return redirect()->route('property.show', $id)->with('success', 'Property updated successfully.');
    }

    public function addToFund(Request $request, $id)
    {
        $request->validate([
            'fundAmount' => 'required|numeric|min:1',
        ]);

        DB::table('properties')->where('id', $id)->increment('collective_fund', $request->fundAmount);

        return redirect()->back()->with('success', 'Your contribution has been added to the collective fund.');
    }

}