<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;

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
        $user = auth()->user();

        // Check if user has sufficient funds
        if (!$user->userHasSufficientFunds($request->bidAmount)) {
            return redirect()->back()->withErrors(['error' => 'Insufficient funds to place this bid.']);
        }

        /* Assuming you have a bids table to store bids
        DB::table('bids')->insert([
            'property_id' => $id,
            'user_id' => auth()->id(),
            'amount' => $request->bidAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        */

        return redirect()->back()->with('success', 'Your bid has been placed successfully.');
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
        Bid::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'amount' => $request->bidAmount,
            'status' => 'pending', // Default status
        ]);

        return redirect()->back()->with('success', 'Your bid has been placed successfully!');
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

            return redirect()->back()->with('success', 'Your bid has been removed successfully.');
        }

        return redirect()->back()->with('error', 'No bid found to remove.');
    }

    public function show($id)
    {
        
        $property = Property::findOrFail($id);
        $userBid = null;
        $user = auth()->user();

        if ($user) {
            $userBid = Bids::where('property_id', $id)
                ->where('user_id', $user->id)
                ->first(); // Retrieve the user's bid
        }

        return view('property', [
            'property' => $property,
            'userBid' => $userBid, // Pass the user's bid to the view
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
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'beds' => 'required|integer',
            'baths' => 'required|integer',
            'size' => 'required|numeric',
            'type' => 'required|string|max:255',
            'berRating' => 'required|string|max:255',
            'address' => 'required|string',
            'eircode' => 'required|string|max:20',
            'description' => 'required|string',
            'features' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate each image
        ]);

        $propertyData = $request->except('images');

        // Handle features as an array
        if ($request->has('features')) {
            $propertyData['features'] = explode(',', $request->features);
        }

        // Handle image uploads
        if ($request->hasFile('images')) {
            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public'); // Store in 'storage/app/public/properties'

                // Check if the file was actually saved
                if (!Storage::disk('public')->exists($path)) {
                    return redirect()->back()->with('error', 'Failed to save the image: ' . $image->getClientOriginalName());
                }

                $uploadedImages[] = $path;
            }
            $propertyData['images'] = $uploadedImages; // Save image paths as an array
        }

        $property = Property::create($propertyData);

        return redirect()->route('properties.index')->with('success', 'Property created successfully!');
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
        return redirect()->route('property.show', $property->id)->with('success', 'Property updated successfully!');
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