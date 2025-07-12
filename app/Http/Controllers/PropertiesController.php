<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Property; // Ensure this line is present
use App\Models\Bids;
use App\Models\Image;
use Log;

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
    
        // Only filter to active properties if filter button was pressed
        if ($request->input('filter') === 'activeBids') {
            $query->where('status', 1);
        }
    
        // Filter by Eircode region if provided
        if ($request->filled('eircodeRegion')) {
            $query->where('eircode', 'LIKE', $request->eircodeRegion . '%');
        }
    
        // Fetch filtered properties
        $properties = $query->with('images')->get();
    
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

        // Find existing bid for this property and user
        $existingBid = $property->bids()->where('user_id', $user->id)->first();
        $newBidAmount = $request->bidAmount;

        // Calculate the current total of all other bids
        $currentTotal = $property->bids()
            ->where('user_id', '!=', $user->id)
            ->sum('amount');

        // Calculate the new total if this bid is accepted
        $newTotal = $currentTotal + $newBidAmount;
        $fundGoal = $property->price;

        // Prevent overfunding
        if ($newTotal > $fundGoal) {
            $maxAllowed = $fundGoal - $currentTotal;
            return response()->json([
                'success' => false,
                'message' => 'Your bid would overfund this property. The maximum you can bid is €' . number_format($maxAllowed, 2) . '.',
                'userbalance' => (float) $user->balance,
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $fundGoal,
                'userBid' => $existingBid ? $existingBid->amount : null,
            ]);
        }

        // Existing bid logic...
        if ($existingBid) {
            $difference = $newBidAmount - $existingBid->amount;

            // If the bid amount is the same, return a message
            if ($difference == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your new bid is the same as your current bid. No changes were made.',
                    'userbalance' => (float) $user->balance,
                    'collectiveFund' => $property->getCollectiveFunds(),
                    'fundGoal' => $property->price,
                    'userBid' => $existingBid->amount,
                ]);
            }

            // If increasing bid, check if user has enough funds for the difference
            if ($difference > 0 && $user->balance < $difference) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough funds to increase your bid.',
                    'userbalance' => (float) $user->balance,
                    'collectiveFund' => $property->getCollectiveFunds(),
                    'fundGoal' => $property->price,
                    'userBid' => $existingBid->amount,
                ]);
            }

            // Adjust user balance
            $user->balance -= $difference;
            $user->save();

            // Update the existing bid
            $existingBid->amount = $newBidAmount;
            $existingBid->save();

            return response()->json([
                'success' => true,
                'message' => 'Your bid has been updated successfully!',
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $property->price,
                'userbalance' => (float) $user->balance,
                'userBid' => $newBidAmount,
            ]);
        } else {
            // New bid logic...
            if ($user->balance < $newBidAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have enough funds to place this bid.',
                    'userbalance' => (float) $user->balance,
                    'collectiveFund' => $property->getCollectiveFunds(),
                    'fundGoal' => $property->price,
                    'userBid' => null,
                ]);
            }

            // Deduct the bid amount from the user's funds
            $user->balance -= $newBidAmount;
            $user->save();

            // Create a new bid entry
            $property->bids()->create([
                'user_id' => $user->id,
                'amount' => $newBidAmount,
                'status' => 'pending', // or your default status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your bid has been placed successfully!',
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $property->price,
                'userbalance' => (float) $user->balance,
                'userBid' => $newBidAmount,
            ]);
        }
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
            $user->balance += $existingBid->amount;
            $user->save();
    
            // Delete the bid
            $existingBid->delete();
    
            // Return a JSON response with a success message
            return response()->json([
                'success' => true,
                'message' => 'Your bid has been removed successfully.',
                'updatedFunds' => $user->balance // Optionally, return updated funds for UI
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
