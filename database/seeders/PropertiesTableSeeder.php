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
                'name' => 'Property 1',
                'price' => 250000,
                'beds' => 3,
                'baths' => 2,
                'size' => 120,
                'type' => 'House',
                'ber_rating' => 'B2',
                'address' => '123 Main St, City, Country',
                'eircode' => 'D01AB23',
                'description' => 'A beautiful house located in the heart of the city.',
                'sale_type' => 'For Sale',
                'stamp_duty' => 5000,
                'features' => json_encode(['garden', 'garage']),
                'auctioneer_name' => 'John Doe',
                'latitude' => 40.712776,
                'longitude' => -74.005974,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Property 2',
                'price' => 300000,
                'beds' => 4,
                'baths' => 3,
                'size' => 150,
                'type' => 'Apartment',
                'ber_rating' => 'A1',
                'address' => '456 Elm St, City, Country',
                'eircode' => 'D24AB23',
                'description' => 'A spacious apartment with modern amenities.',
                'sale_type' => 'For Sale',
                'stamp_duty' => 6000,
                'features' => json_encode(['pool', 'gym']),
                'auctioneer_name' => 'Jane Smith',
                'latitude' => 34.052235,
                'longitude' => -118.243683,
                'status' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
