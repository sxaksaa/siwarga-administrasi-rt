<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@administrasirt.test')],
            [
                'name' => env('ADMIN_NAME', 'Administrator RT'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'AdminRT123!')),
            ],
        );
    }
}
