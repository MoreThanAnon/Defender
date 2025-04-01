<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;

class PropertyController extends Controller
{

    public function __construct()
    {
        //$this->middleware(['auth', 'role:moderator,admin'])->only('store');
    }

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
            'latitude' => $property->latitude ?? 0,
            'longitude' => $property->longitude ?? 0,
            'images' => [],
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
        return redirect()->route('properties.show', $id)->with('success', 'Property updated successfully.');
    }

}