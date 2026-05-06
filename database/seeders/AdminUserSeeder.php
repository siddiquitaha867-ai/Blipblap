<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@blipblap.test'],
            [
                'first_name' => 'BlipBlap',
                'last_name' => 'Admin',
                'name' => 'BlipBlap Admin',
                'password' => 'AdminPass123',
                'email_verified_at' => now(),
                'is_admin' => true,
                'marketing_opt_in' => false,
            ]
        );
    }
}
