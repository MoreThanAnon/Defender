<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Property; // Ensure this line is present
use App\Models\Bids;
use App\Models\Image;

class PropertiesController extends Controller
{
    /**
     * Show the properties page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {


        $query = Property::query();
        
        //Eirocdes
        if ($request->filled('eircodeRegion')) {
            //Don't just fucking put it in here
            $query->where('eircode', 'LIKE', $request->eircodeRegion . '%');
        }
        
        // Fetch filtered properties
        $properties = $query->with('images')->get();
    
        // Return the filtered properties as JSON
        //$properties = Property::get();

        return view('properties', compact('properties'));
    }
    public function setFundGoal(Request $request, $id)
    {
        $request->validate([
            'fundGoal' => 'required|numeric|min:0',
        ]);
    
        $property = Property::findOrFail($id);
        $property->price = $request->input('fundGoal');
        $property->save();
    
        return redirect()->back()->with('success', 'Fund goal updated successfully.');
    }
    public function placeBid(Request $request, $id)
    {   
        $request->validate([
            'bidAmount' => 'required|numeric|min:1',
        ]);

        $property = Property::findOrFail($id);
        $user = auth()->user();

        // Check if the user already has a bid for this property
        $existingBid = Bids::where('property_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingBid) {
            // Refund the previous bid amount to the user's funds
            $user->funds += $existingBid->amount;

            // Check if the user has enough funds for the new bid
            if ($user->funds < $request->bidAmount) {
                return response()->json(['error' => 'You do not have enough funds to place this bid.'], 400);
            }

            // Deduct the new bid amount from the user's funds
            $user->funds -= $request->bidAmount;
            $user->save();

            // Update the existing bid
            $existingBid->update([
                'amount' => $request->bidAmount,
            ]);
        } else {
            file_put_contents(__DIR__ . "\here2.txt","no bid");

            // Check if the user has enough funds for the new bid
            if ($user->funds < $request->bidAmount) {
                return response()->json(['error' => 'You do not have enough funds to place this bid.'], 400);
            }

            // Deduct the bid amount from the user's funds
            $user->funds -= $request->bidAmount;
            $user->save();

            // Create a new bid
            Bids::create([
                'property_id' => $property->id,
                'user_id' => $user->id,
                'amount' => $request->bidAmount,
            ]);
        }
        if ($existingBid){
            $message = "Your bid has been updated successfully!";
        }else{
            $message = "Your bid has been placed successfully!";
        }
        
        // Return a success response
        return response()->json([
            'success' => true,
            'updatedFunds' => $user->funds, // Include updated funds in the response
            'message'=>$message
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
    public function removeBid($id)
    {
        $user = auth()->user();
        // Find the user's bid for the property
        $existingBid = Bids::where('property_id', $id)
            ->where('user_id', $user->id)
            ->first();
    
        if ($existingBid) {
            // Refund the bid amount to the user's funds
            $user->funds += $existingBid->amount;
            $user->save();
    
            // Delete the bid
            $existingBid->delete();
    
            // Return a JSON response with a success message
            return response()->json([
                'success' => true,
                'message' => 'Your bid has been removed successfully.',
                'updatedFunds' => $user->funds // Optionally, return updated funds for UI
            ]);
        }
    
        // Return a JSON response if no bid found to remove
        return response()->json([
            'success' => false,
            'message' => 'No bid found to remove.'
        ], 404); // Return 404 for not found case
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
            'daft_link' => 'nullable|url',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,jfif|max:2048', // Validate images
        ]);

        $property = Property::findOrFail($id);

        // Update property details
        $property->update($validatedData);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                // Create a folder path based on the property ID
                $folderPath = 'properties/' . $id;
                //C:\wamp644\www\defender\storage\app\public\properties\{id}\filename.jpg

                // Store the image in the folder
                $path = $image->store($folderPath, 'public'); // Store in 'storage/app/public/properties/{id}'

                // Save the image path in the database
                Image::create([
                    'property_id' => $id,
                    'path' => $path,
                ]);
            }
        }

        // Redirect to the property page after updating
        return redirect()->route('properties.edit', $property->id)->with('success', 'Property updated successfully!');
    }
}
