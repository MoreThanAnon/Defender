<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use App\Models\Bids;
class PropertyController extends Controller
{

    public function __construct()
    {
        //$this->middleware(['auth', 'role:moderator,admin'])->only('store');
    }

    public function setStatus(Request $request)
    {
        // Find the property by ID
        $property = Property::findOrFail($request->id);
    
        // Update the status field
        $property->status = $request->status; // Assuming 'status' is coming from the form/request
    
        // Save the changes
        $property->save();
    
        // Optionally, you can redirect or return a response
        return redirect()->back()->with('success', 'Property status updated successfully.');
    }

    /**
     * Show the detailed view of a property.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show($id)
    {
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
            'collectiveFund' => $this->getCollectiveFunds($id),
            'fundGoal' => $property->price ?? 1,
            'daft_link' => $property->daft_link,
            'userBid' => $userBid,
            'bids' => $bids,
            'images' => $images,
            'property_status'=>$property->status,
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

    public function getCollectiveFunds($id){
        $fundings = Bids::where('property_id', $id)->sum('amount');
        return $fundings;
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
            'ber_rating' => 'required|string|max:255',
            'address' => 'required|string',
            'eircode' => 'required|string|max:20',
            'description' => 'required|string',
            'features' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate each file
        ]);
    
        $propertyData = $request->except('images');
        
        if ($request->filled('features')) {
            $propertyData['features'] = json_encode(explode(',', $request->features));
        }
        $uploadedImages = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $uploadedImages[] = $path;
            }
            $propertyData['images'] = $uploadedImages;
        }
        $property = Property::create($propertyData);
        foreach($uploadedImages as $imagePath) {
            Image::create([
                'property_id' => $property->id,
                'path' => $imagePath,
            ]);
        }
        return redirect()->route('properties.index')->with('success', 'Property created successfully!');
    
}
    

    public function edit($id)
    {
        $property = Property::find($id);
        $images = Image::where('property_id', $id)->get();
        if (!$property) {
            abort(404, 'Property not found');
        }
        return view('property_form', [
            'property' => $property,
            'images' => $images,
        ]);
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
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,jfif|max:2048',
        ]);
    
        $property = Property::findOrFail($id);
        $property->update($validatedData);
        if ($request->has('delete_images')) {
            foreach ($request->input('delete_images') as $imagePath) {
                // Delete the image file from storage
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
    
                // Remove from DB (assuming 'path' column is unique enough)
                Image::where('property_id', $id)
                    ->where('path', $imagePath)
                    ->delete();
            }
        }
    
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $folderPath = 'properties/' . $id;
                $path = $image->store($folderPath, 'public');
    
                Image::create([
                    'property_id' => $id,
                    'path' => $path,
                ]);
            }
        }
    
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