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
        $userBid = null;
        $user = auth()->user();
        $property = Property::with('images')->findOrFail($id);

        if ($user) {
            $userBid = Bids::where('property_id', $id)
                ->where('user_id', $user->id)
                ->first();
        }
        $bids = Bids::where('property_id', $id)->get(); // Retrieve all bids for the property
        $images = Image::where('property_id', $id)->get();

        $collectiveFund = "";
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
            'collectiveFund' => $property->collective_fund,
            'fundGoal' => $property->fund_goal ?? 1,
            'daft_link' => $property->daft_link,
            'userBid' => $userBid,
            'bids' => $bids,
            'images' => $images,
        ]);
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
            $user->funds += $existingBid->amount;
            $user->save();

            // Delete the bid
            $existingBid->delete();

            return redirect()->back()->with('success', 'Your bid has been removed successfully.');
        }

        return redirect()->back()->with('error', 'No bid found to remove.');
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
