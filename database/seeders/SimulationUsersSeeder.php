<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SimulationUsersSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $now = Carbon::now();
        $password = Hash::make('password');

        for ($i = 1; $i <= 100; $i++) {
            $balance = fake()->randomFloat(2, 5000, 25000);
            $gross = round($balance / 0.95, 2);
            $siteFee = round($gross - $balance, 2);

            $user = User::updateOrCreate(
                ['email' => sprintf('sim-user-%03d@example.com', $i)],
                [
                    'username' => sprintf('Simulation User %03d', $i),
                    'real_name' => sprintf('Simulation User %03d', $i),
                    'password' => $password,
                    'role' => 'user',
                    'balance' => $balance,
                    'funds' => $balance,
                    'email_verified_at' => $now,
                    'eircode_region' => fake()->randomElement(['D01', 'D02', 'D04', 'D06', 'D08', 'D12', 'D18', 'D24']),
                ]
            );

            $user->roles()->syncWithoutDetaching([$userRole->id]);

            DB::table('deposits')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'gross_amount' => $gross,
                    'site_fee' => $siteFee,
                    'net_amount' => $balance,
                    'created_at' => $now->copy()->subMinutes($i % 30),
                ]
            );
        }
    }
}
