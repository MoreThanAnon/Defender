<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    public function setStatus(Request $request, $id, $status)
    {
        $request->merge(['status' => $status]);

        $validated = $request->validate([
            'status' => 'required|exists:properties_status,id',
        ]);

        $property = Property::findOrFail($id);
        $property->status = $validated['status'];
        $property->save();

        return redirect()->back()->with('success', 'Property status updated successfully.');
    }

    public function show($id)
    {
        $user = auth()->user();
        $property = Property::with([
            'images',
            'updates' => fn ($query) => $query->latest(),
        ])->findOrFail($id);

        $propertyRegion = $property->eircode
            ? strtoupper(substr(preg_replace('/\s+/', '', $property->eircode), 0, 3))
            : null;
        $userRegion = $user?->eircode_region ? strtoupper($user->eircode_region) : null;

        $campaignStart = $property->campaign_start ? Carbon::parse($property->campaign_start) : null;
        $campaignEnd = $property->campaign_end ? Carbon::parse($property->campaign_end) : null;
        $internalDays = max((int) ($property->internal_days ?? 0), 0);
        $internalEnd = $campaignStart?->copy()->addDays($internalDays);

        $isLocalUser = $user && $propertyRegion && $userRegion && $propertyRegion === $userRegion;
        $localsOnly = $property->status == 1 && $internalEnd && now()->lt($internalEnd) && ! $isLocalUser;
        $userBid = $user ? $property->bids()->where('user_id', $user->id)->first() : null;
        $features = is_array($property->features)
            ? $property->features
            : array_filter(array_map('trim', explode(',', (string) $property->features)));
        $imagePaths = collect([$property->cover_image])
            ->merge($property->images->pluck('path'))
            ->filter()
            ->unique()
            ->values();

        return view('property', [
            'id' => $property->id,
            'name' => $property->name,
            'price' => $property->price,
            'localsOnly' => $localsOnly,
            'propertyRegion' => $propertyRegion,
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
            'features' => $features,
            'auctioneerName' => $property->auctioneer_name,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'collectiveFund' => $property->getCollectiveFunds(),
            'fundGoal' => $property->price ?? 1,
            'daft_link' => $property->daft_link,
            'userBid' => $userBid,
            'bids' => $property->bids()->get(),
            'images' => $property->images,
            'imagePaths' => $imagePaths,
            'updates' => $property->updates,
            'property_status' => $property->status,
            'campaign_start' => $property->campaign_start,
            'campaign_end' => $property->campaign_end,
            'internal_days' => $property->internal_days,
            'campaignStart' => $campaignStart,
            'campaignEnd' => $campaignEnd,
            'internalDays' => $internalDays,
            'isLocalUser' => $isLocalUser,
            'userRegion' => $userRegion,
        ]);
    }

    public function create()
    {
        return view('property_form', [
            'property' => new Property(),
            'images' => [],
        ]);
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
            'ber_rating' => 'required|string|max:10',
            'address' => 'required|string',
            'eircode' => 'required|string|max:20',
            'description' => 'required|string',
            'features' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'daft_link' => 'nullable|url',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'campaign_start' => 'nullable|date',
            'campaign_end' => 'nullable|date|after_or_equal:campaign_start',
            'internal_days' => 'nullable|integer|min:0',
        ]);

        $propertyData = $request->except('images');

        if ($request->filled('features')) {
            $propertyData['features'] = array_filter(array_map('trim', explode(',', $request->features)));
        }

        $propertyData['campaign_start'] = $request->campaign_start;
        $propertyData['campaign_end'] = $request->campaign_end;
        $propertyData['internal_days'] = $request->internal_days ?? 0;

        if ($request->hasFile('cover_image')) {
            $propertyData['cover_image'] = $request->file('cover_image')->store('properties/cover_images', 'public');
        }

        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploadedImages[] = $image->store('properties', 'public');
            }
        }

        $property = Property::create($propertyData);

        foreach ($uploadedImages as $imagePath) {
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

        if (! $property) {
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
            'ber_rating' => 'required|string|max:10',
            'address' => 'required|string|max:255',
            'eircode' => 'nullable|string|max:20',
            'description' => 'required|string',
            'features' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'daft_link' => 'nullable|url',
            'images' => 'nullable|array',
            'images.*' => 'file|image|mimes:jpeg,jpg,png,gif|max:2048',
            'cover_image' => 'nullable|file|image|mimes:jpeg,jpg,png,gif|max:2048',
            'campaign_start' => 'nullable|date',
            'campaign_end' => 'nullable|date|after_or_equal:campaign_start',
            'internal_days' => 'nullable|integer|min:0',
        ]);

        $property = Property::findOrFail($id);
        $property->fill([
            'name' => $validatedData['name'],
            'price' => $validatedData['price'],
            'beds' => $validatedData['beds'],
            'baths' => $validatedData['baths'],
            'size' => $validatedData['size'],
            'type' => $validatedData['type'],
            'ber_rating' => $validatedData['ber_rating'],
            'address' => $validatedData['address'],
            'eircode' => $validatedData['eircode'] ?? null,
            'description' => $validatedData['description'],
            'features' => $request->filled('features')
                ? array_filter(array_map('trim', explode(',', $request->features)))
                : null,
            'latitude' => $validatedData['latitude'] ?? null,
            'longitude' => $validatedData['longitude'] ?? null,
            'daft_link' => $validatedData['daft_link'] ?? null,
            'campaign_start' => $validatedData['campaign_start'] ?? null,
            'campaign_end' => $validatedData['campaign_end'] ?? null,
            'internal_days' => $validatedData['internal_days'] ?? 0,
        ]);

        if ($request->boolean('delete_cover_image') && $property->cover_image) {
            Storage::disk('public')->delete($property->cover_image);
            $property->cover_image = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($property->cover_image) {
                Storage::disk('public')->delete($property->cover_image);
            }

            $property->cover_image = $request->file('cover_image')->store('properties/cover_images', 'public');
        }

        $property->save();

        foreach ($request->input('delete_images', []) as $imagePath) {
            Storage::disk('public')->delete($imagePath);
            Image::where('property_id', $id)->where('path', $imagePath)->delete();
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store("properties/{$id}", 'public');

                Image::create([
                    'property_id' => $id,
                    'path' => $path,
                ]);
            }
        }

        return redirect()
            ->route('property.show', $property->id)
            ->with('success', 'Property updated successfully!');
    }
}
