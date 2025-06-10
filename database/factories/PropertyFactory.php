<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->streetName,
            'price' => $this->faker->numberBetween(100000, 500000),
            'beds' => $this->faker->numberBetween(1, 5),
            'baths' => $this->faker->numberBetween(1, 3),
            'size' => $this->faker->numberBetween(50, 300),
            'type' => 'House',
            'ber_rating' => 'B2',
            'address' => $this->faker->address,
            'description' => $this->faker->paragraph,
            'sale_type' => 'For Sale',
            'stamp_duty' => 5000,
            'features' => json_encode(['garden', 'garage']),
            'auctioneer_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'eircode' => 'D01AB23',
            'status' => 1,
        ];
    }
}