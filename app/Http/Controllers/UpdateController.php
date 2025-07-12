<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Update;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    public function create(Property $property)
    {
        return view('updates.create', compact('property'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'nullable|mimes:pdf,doc,docx,jpg,png,xlsx|max:5120',
        ]);

        $data = $request->only(['property_id','title','content','description','author']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('updates/images', 'public');
        }

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')->store('updates/files', 'public');
            $data['file_type'] = $request->file('file')->extension();
        }

        Update::create($data);

        return redirect()->route('property.show', $request->property_id)
            ->with('success', 'Update added successfully');
    }
}
