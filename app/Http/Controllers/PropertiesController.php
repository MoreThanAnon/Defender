<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property; // Ensure this line is present
use App\Models\Bids;

class PropertiesController extends Controller
{
    /**
     * Show the properties page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Fetch properties data from the database
        $properties = Property::all();

        return view('properties', compact('properties'));
    }

    /**
     * Show the detailed view of a property.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show($id)
    {
        $property = Property::findOrFail($id);

        return view('property', [
            'id' => $property->id,
            'name' => $property->name,
            'price' => $property->price,
            'beds' => $property->beds,
            'baths' => $property->baths,
            'size' => $property->size,
            'type' => $property->type,
            'ber_rating' => $property->ber_rating,
            'eircode' => $property->eircode,
            'address' => $property->address,
            'description' => $property->description,
            'saleType' => $property->sale_type,
            'stampDuty' => $property->stamp_duty,
            'features' => $property->features,
            'auctioneerName' => $property->auctioneer_name,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'images' => $property->images,
            'collectiveFund' => $property->collective_fund, // Pass collective fund
            'fundGoal' => $property->fund_goal ?? 1, // Pass fund goal
            'daft_link'=> $property->daft_link,
        ]);
    }
    public function placeBid(Request $request, $id)
    {
        $request->validate([
            'bidAmount' => 'required|numeric|min:1',
        ]);

        $property = Property::findOrFail($id);
        $user = auth()->user();

        // Check if the user has enough funds
        if ($user->funds < $request->bidAmount) {
            return redirect()->back()->with('error', 'You do not have enough funds to place this bid.');
        }

        // Deduct the bid amount from the user's funds
        $user->funds -= $request->bidAmount;
        $user->save();

        // Create a new bid entry
        Bids::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'amount' => $request->bidAmount,
            'status' => 'pending', // Default status
        ]);

        return redirect()->back()->with('success', 'Your bid has been placed successfully!');
    }
    /**
     * Store a newly created property in the database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'beds' => 'required|integer',
            'baths' => 'required|integer',
            'size' => 'required|numeric',
            'type' => 'required|string|max:50',
            'ber_rating' => 'nullable|string|max:10',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sale_type' => 'nullable|string|max:50',
            'stamp_duty' => 'nullable|numeric',
            'features' => 'nullable|array',
            'auctioneer_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'eircode' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9]{3}[ ]?[A-Za-z0-9]{4}$/', // Validate Irish Eircode format
            ],
        ]);

        $property = Property::create($validatedData);

        return response()->json([
            'message' => 'Property created successfully',
            'property' => $property
        ], 201);
    }

    /**
     * Update the specified property in the database.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'beds' => 'required|integer',
            'baths' => 'required|integer',
            'size' => 'required|numeric',
            'type' => 'required|string|max:50',
            'berRating' => 'nullable|string|max:10',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $property = Property::findOrFail($id);
        $property->update($validatedData);

        // Redirect to the property page after updating
        return redirect()->route('properties.show', $property->id)->with('success', 'Property updated successfully!');
    }
}
