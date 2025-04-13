<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DepositsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assuming users with IDs 1 to 3 exist
        for ($i = 1; $i <= 10; $i++) {
            $gross = rand(1000, 10000) / 100; // e.g. $10.00 to $100.00
            $siteFee = round($gross * 0.05, 2); // 5% fee
            $net = $gross - $siteFee;

            DB::table('deposits')->insert([
                'user_id' => rand(1, 3), // Adjust based on existing users
                'gross_amount' => $gross,
                'site_fee' => $siteFee,
                'net_amount' => $net,
                'created_at' => Carbon::now()
            ]);
        }
        DB::table('deposits')->insert([
            'user_id' => 1, // Adjust based on existing users
            'gross_amount' => 1000,
            'site_fee' => 50.50,
            'net_amount' => 949.50,
            'created_at' => Carbon::now()
        ]);
    }
}