<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bids; // Corrected namespace
use App\Models\Property;

class BidsController extends Controller
{
    public function submitBid(Request $request, $id)
    {
        $request->validate([
            'bidAmount' => 'required|numeric|min:0.01',
        ]);

        $property = Property::findOrFail($id);

        // Create an instance of the Bids model
        $bid = new Bids();

        // Call the placeBid method on the instance
        $bid->placeBid($request, $property->id);

        return response()->json(['success' => true, 'message' => 'Bid submitted successfully.']);
    }
}
