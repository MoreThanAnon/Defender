<?php

namespace App\Http\Controllers;

use App\Models\Bids;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertiesController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::query();

        if ($request->input('filter') === 'activeBids') {
            $query->where('status', 1);
        }

        if ($request->filled('eircodeRegion')) {
            $query->where('eircode', 'LIKE', $request->eircodeRegion . '%');
        }

        $properties = $query->with('images')->get();

        return view('properties', compact('properties'));
    }

    public function placeBid(Request $request, $id)
    {
        $request->validate([
            'bidAmount' => 'required|numeric|min:1',
        ]);

        $property = Property::findOrFail($id);
        $user = $request->user();
        $existingBid = $property->bids()->where('user_id', $user->id)->first();
        $newBidAmount = $request->bidAmount;

        $currentTotal = $property->bids()
            ->where('user_id', '!=', $user->id)
            ->sum('amount');

        $newTotal = $currentTotal + $newBidAmount;
        $fundGoal = $property->price;

        if ($newTotal > $fundGoal) {
            $maxAllowed = $fundGoal - $currentTotal;

            return response()->json([
                'success' => false,
                'message' => 'Your bid would overfund this property. The maximum you can bid is EUR ' . number_format($maxAllowed, 2) . '.',
                'userbalance' => (float) $user->balance,
                'collectiveFund' => $property->getCollectiveFunds(),
                'fundGoal' => $fundGoal,
                'userBid' => $existingBid ? $existingBid->amount : null,
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
            $user->save();

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
        $user->save();

        $property->bids()->create([
            'user_id' => $user->id,
            'amount' => $newBidAmount,
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
        $existingBid = Bids::where('property_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingBid) {
            $user->balance += $existingBid->amount;
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
