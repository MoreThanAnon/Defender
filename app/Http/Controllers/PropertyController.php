<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use App\Models\Bids;
use Carbon\Carbon;

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

        // Extract property eircode region (first 3 characters)
        $propertyRegion = $property->eircode ? substr($property->eircode, 0, 3) : null;
        $userRegion = $user && $user->eircode_region ? $user->eircode_region : null;

        // Calculate locals-only period
        $campaignStart = $property->campaign_start ? \Carbon\Carbon::parse($property->campaign_start) : null;
        $now = now();
        $localsOnly = false;
        if ($campaignStart && $property->internal_days > 0) {
            $localsOnlyEnd = $campaignStart->copy()->addDays($property->internal_days);
            $localsOnly = $now->between($campaignStart, $localsOnlyEnd);
        }

        // Is user local?
        $isLocalUser = $user && $propertyRegion && $userRegion && $propertyRegion === $userRegion;
        if ($user) {
            $userBid = Bids::where('property_id', $id)
                ->where('user_id', $user->id)
                ->first();
        }

        $bids = Bids::where('property_id', $id)->get(); // Retrieve all bids for the property
        $images = Image::where('property_id', $id)->get();

        // Add campaign timing variables
        $campaignStart = $property->campaign_start ? \Carbon\Carbon::parse($property->campaign_start) : null;
        $campaignEnd = $property->campaign_end ? \Carbon\Carbon::parse($property->campaign_end) : null;
        $internalDays = $property->internal_days ?? 0;
        $internalEnd = Carbon::parse($property->campaign_start)->addDays($property->internal_days);
        $now = Carbon::now();
// This line checks if the current time is still within the internal bidding period
// AND if the logged-in user's eircode region is NOT the same as the property's eircode region.
// If both are true, then locals-only bidding is in effect and the current user is NOT local.
$localsOnly = $now->lessThan($internalEnd) && (auth()->user()->eircode_region !== $property->eircode_region);

        return view('property', [
            'id' => $property->id,
            'name' => $property->name,
            'price' => $property->price,
            'localsOnly' => $localsOnly,
            'propertyRegion' => $property->eircode_region,
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
            'property_status' => $property->status,
            'campaign_start' => $property->campaign_start,
            'campaign_end' => $property->campaign_end,
            'internal_days' => $property->internal_days,
            // Optionally, pass Carbon instances if you want to use them in JS
            'campaignStart' => $property->campaign_start,
            'campaignEnd' => $property->campaign_end,
            'internalDays' => $property->internal_days,
            'isLocalUser' => $isLocalUser,
            'propertyRegion' => $propertyRegion,
            'userRegion' => $userRegion,
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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'campaign_start' => 'nullable|date',
            'campaign_end' => 'nullable|date',
            'internal_days' => 'nullable|integer|min:0',
        ]);

        $propertyData = $request->except('images');
        if ($request->filled('features')) {
            $propertyData['features'] = json_encode(explode(',', $request->features));
        }
        $propertyData['campaign_start'] = $request->campaign_start;
        $propertyData['campaign_end'] = $request->campaign_end;
        $propertyData['internal_days'] = $request->internal_days ?? 0;

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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'campaign_start' => 'nullable|date',
            'campaign_end' => 'nullable|date',
            'internal_days' => 'nullable|integer|min:0',
        ]);

        $property = Property::findOrFail($id);

        $property->ber_rating = $request->berRating ?? $property->ber_rating;
        $property->campaign_start = $request->campaign_start;
        $property->campaign_end = $request->campaign_end;
        $property->internal_days = $request->internal_days ?? 0;
        // ✅ Handle cover image deletion
        if ($request->has('delete_cover_image')) {
            if ($property->cover_image && Storage::disk('public')->exists($property->cover_image)) {
                Storage::disk('public')->delete($property->cover_image);
            }
            $property->cover_image = null;
        }

        // ✅ Handle new cover image upload
        if ($request->hasFile('cover_image')) {
            if ($property->cover_image && Storage::disk('public')->exists($property->cover_image)) {
                Storage::disk('public')->delete($property->cover_image);
            }
            $coverImagePath = $request->file('cover_image')->store('properties/cover_images', 'public');
            $property->cover_image = $coverImagePath;
        }

        $property->save();

        // ✅ Handle deletion of gallery images
        if ($request->has('delete_images')) {
            foreach ($request->input('delete_images') as $imagePath) {
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                Image::where('property_id', $id)
                    ->where('path', $imagePath)
                    ->delete();
            }
        }

        // ✅ Handle new gallery image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store("properties/{$id}", 'public');
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