<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PropertiesController extends Controller
{
    /**
     * Show the properties page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Fetch properties data from the database
        $properties = [
            // Example properties data
            (object) [
                'id' => 1, 
                'name' => 'Property 1', 
                'price' => 250000, 
                'beds' => 3, 
                'baths' => 2, 
                'type' => 'House',
                'size' => 120,
                'berRating' => 'B2',
                'address' => '123 Main St, City, Country',
                'description' => 'A beautiful house located in the heart of the city.'
            ],
            (object) [
                'id' => 2, 
                'name' => 'Property 2', 
                'price' => 300000, 
                'beds' => 4, 
                'baths' => 3, 
                'type' => 'Apartment',
                'size' => 150,
                'berRating' => 'A1',
                'address' => '456 Elm St, City, Country',
                'description' => 'A spacious apartment with modern amenities.'
            ],
        ];

        return view('properties', compact('properties'));
    }

    /**
     * Show the detailed view of a property.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show($id)
    {
        // Fetch property data from the database
        $property = (object) [
            'id' => $id,
            'name' => 'Property ' . $id,
            'price' => 250000,
            'beds' => 3,
            'baths' => 2,
            'type' => 'House',
            'size' => 120,
            'berRating' => 'B2',
            'address' => '123 Main St, City, Country',
            'description' => 'A beautiful house located in the heart of the city.',
            'images' => [
                'path/to/image1.jpg',
                'path/to/image2.jpg',
            ],
        ];

        return view('property', compact('property'));
    }
}
