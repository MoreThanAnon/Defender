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

        $this->call(PropertiesTableSeeder::class);
        $this->call(EircodeAddressesTableSeeder::class);

        // Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create an admin user and assign role
        $admin = User::create([
            'username' => 'anon',
            'email' => 'anon@anon.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->roles()->attach($adminRole->id);

        // Create a regular user and assign role
        $user = User::create([
            'username' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);
        $user->roles()->attach($userRole->id);
    }
}
