<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_displayed(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
        $response->assertSee('ログイン');
    }

    public function test_register_page_can_be_displayed(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('お名前');
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
        $response->assertSee('パスワード確認');
        $response->assertSee('登録');
    }

    public function test_user_can_register(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(('/'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_required_fields(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
        ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_can_login(): void
    {
        $user = user::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'email',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');

        $this->assertGuest();
    }
}