<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Bids extends Model
{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'user_id',
        'amount',
        'status',
    ];

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

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
