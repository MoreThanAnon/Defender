<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefenderOrgStewardSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'org_steward']);

        $user = User::updateOrCreate(
            ['email' => config('defender.org_steward.email')],
            [
                'username' => config('defender.org_steward.username'),
                'real_name' => config('defender.org_steward.username'),
                'password' => Hash::make(config('defender.org_steward.password')),
                'role' => 'org_steward',
                'balance' => 0,
                'funds' => 0,
                'email_verified_at' => now(),
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
