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
        $email = env('ADMIN_EMAIL', 'admin@gmail.com');
        $user = User::whereIn('email', [$email, 'admin@administrasirt.test'])->first() ?? new User;
        $user->fill([
            'name' => env('ADMIN_NAME', 'Admin'),
            'email' => $email,
            'password' => Hash::make(env('ADMIN_PASSWORD', 'admin')),
        ])->save();
    }
}
