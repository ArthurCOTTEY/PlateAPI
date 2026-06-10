<?php

namespace Tests\Feature;

use App\Models\ApiLogs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_see_account(): void
    {
        $response = $this->getJson('/api/account');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_see_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/account');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Admin',
                    'email' => 'admin@example.com',
                ],
            ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'route' => 'api/account',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_cannot_see_account_actions(): void
    {
        $response = $this->getJson('/api/account/actions');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_see_account_actions(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        ApiLogs::create([
            'method' => 'POST',
            'route' => 'api/tokens/create',
            'ip' => '127.0.0.1',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);

        ApiLogs::create([
            'method' => 'GET',
            'route' => 'api/account',
            'ip' => '127.0.0.1',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/account/actions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'method',
                        'ip',
                        'email',
                        'at',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'route' => 'api/account/actions',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_account_actions_are_paginated_by_ten(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        ApiLogs::factory()->count(15)->create([
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/account/actions');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_guest_cannot_update_account(): void
    {
        $response = $this->patchJson('/api/account/update', [
            'name' => 'Admin Updated',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_update_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/account/update', [
                'name' => 'Admin Updated',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Admin Updated',
                    'email' => 'admin@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Admin Updated',
            'email' => 'admin@example.com',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'PATCH',
            'route' => 'api/account/update',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_update_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        ApiLogs::create([
            'method' => 'GET',
            'route' => 'api/account',
            'ip' => '127.0.0.1',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/account/update', [
                'email' => 'admin.updated@example.com',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Admin',
                    'email' => 'admin.updated@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'admin.updated@example.com',
        ]);

        $this->assertDatabaseMissing('api_logs', [
            'user_id' => $user->id,
            'email' => 'admin@example.com',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $user->id,
            'email' => 'admin.updated@example.com',
        ]);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('old-password'),
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/account/update', [
                'password' => 'new-password',
            ]);

        $response->assertOk();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }

    public function test_authenticated_user_can_update_name_email_and_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('old-password'),
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/account/update', [
                'name' => 'Admin Updated',
                'email' => 'admin.updated@example.com',
                'password' => 'new-password',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Admin Updated',
                    'email' => 'admin.updated@example.com',
                ],
            ]);

        $user->refresh();

        $this->assertSame('Admin Updated', $user->name);
        $this->assertSame('admin.updated@example.com', $user->email);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_email_must_be_valid_when_updating_account(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/account/update', [
                'email' => 'invalid-email',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_must_have_at_least_eight_characters_when_updating_account(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/account/update', [
                'password' => 'short',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_send_empty_update_request_without_changing_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/account/update', []);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Admin',
                    'email' => 'admin@example.com',
                ],
            ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'PATCH',
            'route' => 'api/account/update',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }
}
