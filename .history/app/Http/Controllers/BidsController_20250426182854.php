<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Bid;
use App\Model\Property;

class BidsController extends Controller
{
    public function submitBid(Request $request, $id)
    {
        $request->validate([
            'bidAmount' => 'required|numeric|min:0.01',
        ]);
    
        $property = Property::findOrFail($id);
    
        $bid = Bid::updateOrCreate(
            ['user_id' => auth()->id(), 'property_id' => $property->id],
            ['amount' => $request->input('bidAmount')]
        );
    
        return response()->json(['success' => true, 'message' => 'Bid submitted successfully.']);
    }
}
