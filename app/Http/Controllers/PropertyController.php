<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;

class PropertyController extends Controller
{
    // ...existing code...

    public function show($id)
    {
        $property = Property::findOrFail($id);
        return view('property.show', compact('property'));
    }

    // ...existing code...
}