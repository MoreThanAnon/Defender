<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the platform administrator if there is none. An existing admin is never touched.
     * Set DEFENDER_ADMIN_EMAIL / DEFENDER_ADMIN_PASSWORD in .env, or a random password is printed once.
     */
    public function run(): void
    {
        if (! User::where('role', User::ROLE_ADMIN)->exists()) {
            $password = env('DEFENDER_ADMIN_PASSWORD') ?: Str::password(20);

            $admin = new User([
                'username' => 'admin',
                'email' => env('DEFENDER_ADMIN_EMAIL', 'admin@example.com'),
                'password' => $password,
            ]);
            $admin->role = User::ROLE_ADMIN;
            $admin->email_verified_at = now();
            $admin->save();

            if (! env('DEFENDER_ADMIN_PASSWORD')) {
                $this->command?->warn("Platform admin created: username \"admin\", password: $password  (shown once; change it under My account)");
            }
        }

        if (app()->environment('local')) {
            $this->call(DemoBusinessSeeder::class);
        }
    }
}
