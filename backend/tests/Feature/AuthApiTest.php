<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_login_dan_logout(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'rahasia123',
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'token', 'pengguna' => ['id', 'nama', 'email']]);

        $this->withToken($response->json('token'))
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logout berhasil.']);
    }

    public function test_login_ditolak_jika_password_salah(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertUnprocessable()
            ->assertJson(['message' => 'Email atau kata sandi tidak sesuai.']);
    }
}
