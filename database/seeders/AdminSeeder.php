<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $admin = User::where('username', 'admin')->first();

        if (! $admin) {
            $admin = User::create([
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]);
        } else {
            $admin->password = Hash::make('admin');
            $admin->role = 'admin';
            $admin->save();
        }

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
