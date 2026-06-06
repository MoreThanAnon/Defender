<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(PropertyStatusSeeder::class);
        $this->call(PropertiesTableSeeder::class);
        $this->call(EircodeAddressesTableSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(DefenderOrgStewardSeeder::class);

        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create a regular user and assign role
        $user = User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'Regular User',
                'email' => 'user@example.com',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );
        $user->roles()->syncWithoutDetaching([$userRole->id]);
    }
}
