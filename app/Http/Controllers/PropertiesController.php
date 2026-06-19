<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertiesController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::query();

        if ($request->input('filter') === 'activeBids' || $request->input('status') === '1') {
            $query->where('status', 1);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->integer('status'));
        }

        if ($request->filled('eircodeRegion')) {
            $query->where('eircode', 'LIKE', $request->eircodeRegion . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        if ($request->filled('beds_min')) {
            $query->where('beds', '>=', $request->integer('beds_min'));
        }

        if ($request->filled('baths_min')) {
            $query->where('baths', '>=', $request->integer('baths_min'));
        }

        if ($request->filled('keyword')) {
            $keyword = '%' . $request->input('keyword') . '%';
            $query->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', $keyword)
                    ->orWhere('address', 'LIKE', $keyword)
                    ->orWhere('description', 'LIKE', $keyword)
                    ->orWhere('ber_rating', 'LIKE', $keyword);
            });
        }

        match ($request->input('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('name'),
        };

        $properties = $query->with('images')->get();
        $propertyStatuses = DB::table('properties_status')->pluck('status', 'id');
        $propertyTypes = Property::whereNotNull('type')->distinct()->orderBy('type')->pluck('type');
        $eircodeRegions = Property::whereNotNull('eircode')
            ->pluck('eircode')
            ->map(fn ($eircode) => strtoupper(substr(preg_replace('/\s+/', '', $eircode), 0, 3)))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $filtersActive = $request->hasAny([
            'filter',
            'status',
            'eircodeRegion',
            'type',
            'price_min',
            'price_max',
            'beds_min',
            'baths_min',
            'keyword',
            'sort',
        ]);

        return view('properties', compact(
            'properties',
            'propertyStatuses',
            'propertyTypes',
            'eircodeRegions',
            'filtersActive'
        ));
    }

    public function placeBid(Request $request, $id)
    {
        $request->validate([
            'bidAmount' => 'required|numeric|min:1',
        ]);

        $property = Property::findOrFail($id);
        $user = $request->user();

        $propertyRegion = $property->eircode
            ? strtoupper(substr(preg_replace('/\s+/', '', $property->eircode), 0, 3))
            : null;
        $userEircode = $user?->eircode_region ?: $user?->address?->eircode;
        $userRegion = $userEircode
            ? strtoupper(substr(preg_replace('/\s+/', '', $userEircode), 0, 3))
            : null;
        $campaignStart = $property->campaign_start ? Carbon::parse($property->campaign_start) : null;
        $campaignEnd = $property->campaign_end ? Carbon::parse($property->campaign_end) : null;
        $internalEnd = $campaignStart?->copy()->addDays(max((int) ($property->internal_days ?? 0), 0));
        $campaignIsOpen = $property->status == 1
            && (! $campaignStart || now()->gte($campaignStart))
            && (! $campaignEnd || now()->lte($campaignEnd));
        $isLocalUser = $propertyRegion && $userRegion && $propertyRegion === $userRegion;
        $localsOnly = $campaignIsOpen && $internalEnd && now()->lt($internalEnd) && ! $isLocalUser;

        if (! $campaignIsOpen) {
            return response()->json([
                'success' => false,
                'message' => 'Bidding is not currently open for this property.',
                'userbalance' => (float) $user->balance,
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $property->price,
                'userBid' => null,
            ], 403);
        }

        if ($localsOnly) {
            return response()->json([
                'success' => false,
                'message' => 'This property is currently limited to local bidders from ' . $propertyRegion . '.',
                'userbalance' => (float) $user->balance,
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $property->price,
                'userBid' => null,
            ], 403);
        }

        $existingBid = $property->bids()->where('user_id', $user->id)->first();
        $newBidAmount = $request->bidAmount;

        $currentTotal = $property->bids()
            ->where('user_id', '!=', $user->id)
            ->sum('amount');

        $newTotal = $currentTotal + $newBidAmount;
        $fundGoal = $property->price;

        if ($newTotal > $fundGoal) {
            $maxAllowed = $fundGoal - $currentTotal;
            $existingBidAmount = $existingBid ? (float) $existingBid->amount : 0;
            $additionalFundsNeeded = max(0, $maxAllowed - $existingBidAmount);
            $canBidMaxAllowed = $maxAllowed > 0 && $user->balance >= $additionalFundsNeeded;

            return response()->json([
                'success' => false,
                'message' => 'Your bid would overfund this property. The maximum you can bid is EUR ' . number_format($maxAllowed, 2) . '.',
                'maxAllowed' => (float) $maxAllowed,
                'canBidMaxAllowed' => $canBidMaxAllowed,
                'userbalance' => (float) $user->balance,
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $fundGoal,
                'userBid' => $existingBidAmount ?: null,
            ]);
        }

        if ($existingBid) {
            $difference = $newBidAmount - $existingBid->amount;

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

            $user->balance -= $difference;
            $user->funds = max(0, (float) $user->funds - $difference);
            $user->save();

            $existingBid->amount = $newBidAmount;
            $existingBid->original_amount_cents = (int) round($newBidAmount * 100);
            $existingBid->is_local = $isLocalUser;
            $existingBid->is_local_at_bid_time = $isLocalUser;
            $existingBid->local_eligibility_reason = [
                'type' => 'property_eircode_region',
                'property_region' => $propertyRegion,
                'user_region' => $userRegion,
            ];
            $existingBid->local_area_version = $propertyRegion;
            $existingBid->save();

            return response()->json([
                'success' => true,
                'message' => 'Your bid has been updated successfully!',
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $property->price,
                'userbalance' => (float) $user->balance,
                'userBid' => $newBidAmount,
            ]);
        }

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

        $user->balance -= $newBidAmount;
        $user->funds = max(0, (float) $user->funds - $newBidAmount);
        $user->save();

        $property->bids()->create([
            'user_id' => $user->id,
            'amount' => $newBidAmount,
            'original_amount_cents' => (int) round($newBidAmount * 100),
            'is_local' => $isLocalUser,
            'is_local_at_bid_time' => $isLocalUser,
            'local_eligibility_reason' => [
                'type' => 'property_eircode_region',
                'property_region' => $propertyRegion,
                'user_region' => $userRegion,
            ],
            'local_area_version' => $propertyRegion,
            'status' => 'pending',
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

    public function removeBid($id)
    {
        $user = auth()->user();
        $existingBid = Bid::where('property_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingBid) {
            $user->balance += $existingBid->amount;
            $user->funds += $existingBid->amount;
            $user->save();
            $existingBid->delete();

            return response()->json([
                'success' => true,
                'message' => 'Your bid has been removed successfully.',
                'updatedFunds' => $user->balance,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No bid found to remove.',
        ], 404);
    }
}
