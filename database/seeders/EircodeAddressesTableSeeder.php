<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EircodeAddressesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $eircodeAddresses = [
            [
                'eircode' => 'D01F5P2',
                'address_line_1' => '1 Main Street',
                'address_line_2' => 'Apt 2',
                'town_city' => 'Dublin',
                'county' => 'Dublin',
                'latitude' => 53.349805,
                'longitude' => -6.26031,
                'alias' => 'Office',
                'boundary_data' => null,
                'building_info' => 'Commercial',
            ],
            [
                'eircode' => 'A96H2X4',
                'address_line_1' => '2 Elm Grove',
                'address_line_2' => null,
                'town_city' => 'Blackrock',
                'county' => 'Dublin',
                'latitude' => 53.3008,
                'longitude' => -6.1778,
                'alias' => null,
                'boundary_data' => null,
                'building_info' => 'Residential',
            ],
            [
                'eircode' => 'V94T9X9',
                'address_line_1' => '3 Oak Lane',
                'address_line_2' => 'Suite 5',
                'town_city' => 'Limerick',
                'county' => 'Limerick',
                'latitude' => 52.668,
                'longitude' => -8.6305,
                'alias' => 'Warehouse',
                'boundary_data' => null,
                'building_info' => 'Industrial',
            ],
        ];

        DB::table('eircode_addresses')->upsert(
            $eircodeAddresses,
            ['eircode'],
            ['address_line_1', 'address_line_2', 'town_city', 'county', 'latitude', 'longitude', 'alias', 'boundary_data', 'building_info']
        );
    }
}
