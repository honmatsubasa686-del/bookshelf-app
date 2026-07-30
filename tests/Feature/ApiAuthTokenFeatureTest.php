<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTokenFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_can_be_created_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'token@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'token@example.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'token_type' => 'Bearer',
        ]);

        $response->assertJsonStructure([
            'token',
            'token_type',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'test-device',
        ]);
    }

    public function test_api_token_returns_401_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'token@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'token@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'message' => '認証に失敗しました。',
        ]);
    }

    public function test_api_token_requires_device_name(): void
    {
        User::factory()->create([
            'email' => 'token@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'token@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'device_name',
        ]);
    }
}
