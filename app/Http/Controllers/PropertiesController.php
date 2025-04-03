<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property; // Ensure this line is present

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
        $property = Property::find($id);

        if (!$property) {
            abort(404, 'Property not found');
        }

        return view('property', [
            'name' => $property->name ?? 'N/A',
            'price' => $property->price ?? 0,
            'beds' => $property->beds ?? 0,
            'baths' => $property->baths ?? 0,
            'size' => $property->size ?? 0,
            'type' => $property->type ?? 'unknown',
            'berRating' => $property->ber_rating ?? 'N/A',
            'address' => $property->address ?? 'No address provided',
            'description' => $property->description ?? 'No description available',
            'saleType' => $property->sale_type ?? 'N/A',
            'stampDuty' => $property->stamp_duty ?? 0,
            'features' => $property->features ?? [],
            'id' => $property->id,
            'auctioneerName' => $property->auctioneer_name ?? 'Unknown',
            'collectiveFund' => 0, // Placeholder
            'fundGoal'=> 1, // Placeholder
            'latitude' => $property->latitude ?? 0,
            'longitude' => $property->longitude ?? 0
        ]);
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
        ]);

        $property = Property::create($validatedData);

        return response()->json([
            'message' => 'Property created successfully',
            'property' => $property
        ], 201);
    }
}
