<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('properties')->insert([
            [
                'name' => 'Modern Apartment',
                'price' => 250000.00,
                'beds' => 3,
                'baths' => 2,
                'size' => 1200,
                'type' => 'apartment',
                'ber_rating' => 'B2',
                'address' => '123 Main Street, Dublin',
                'description' => 'A beautiful modern apartment in the heart of the city.',
                'sale_type' => 'private',
                'stamp_duty' => 2500.00,
                'features' => json_encode(['balcony', 'parking', 'gym']),
                'auctioneer_name' => 'John Doe',
                'latitude' => 53.349805,
                'longitude' => -6.26031,
                'images' => json_encode(['image1.jpg', 'image2.jpg']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cozy Cottage',
                'price' => 150000.00,
                'beds' => 2,
                'baths' => 1,
                'size' => 800,
                'type' => 'cottage',
                'ber_rating' => 'C1',
                'address' => '456 Country Lane, Galway',
                'description' => 'A charming cottage in a quiet countryside location.',
                'sale_type' => 'auction',
                'stamp_duty' => 1500.00,
                'features' => json_encode(['garden', 'fireplace']),
                'auctioneer_name' => 'Jane Smith',
                'latitude' => 53.270668,
                'longitude' => -9.056791,
                'images' => json_encode(['image3.jpg', 'image4.jpg']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
