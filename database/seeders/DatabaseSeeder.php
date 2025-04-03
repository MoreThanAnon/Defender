<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(PropertiesTableSeeder::class);

        // Create an admin user
        User::create([
            'username' => 'anon',
            'email' => 'anon@anon.com',
            'password' => bcrypt('password'), // Use a secure password
            'role' => 'admin',
        ]);

        // Create a regular user
        User::create([
            'username' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'), // Use a secure password
            'role' => 'user'
        ]);
    }
}
