<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('properties_status')->updateOrInsert(
            ['id' => '1'],
            [
                'status' => 'Active',
                'description' => 'Visible in property listings and open for bidding.',
            ]
        );

        DB::table('properties_status')->updateOrInsert(
            ['id' => '2'],
            [
                'status' => 'Inactive',
                'description' => 'Hidden from public listings and closed to bidding.',
            ]
        );

        DB::table('properties_status')->updateOrInsert(
            ['id' => '3'],
            [
                'status' => 'Funded',
                'description' => 'Funding target reached and property funding records have been resolved.',
            ]
        );
    }
}
