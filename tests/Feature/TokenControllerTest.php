<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class TokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/tokens/create', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'app_name' => 'PlateAPI',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'PlateAPI',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'POST',
            'route' => 'api/tokens/create',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_token_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/tokens/create', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
            'app_name' => 'PlateAPI',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_cannot_create_token_with_unknown_email(): void
    {
        $response = $this->postJson('/api/tokens/create', [
            'email' => 'unknown@example.com',
            'password' => 'password',
            'app_name' => 'PlateAPI',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_email_is_required_to_create_token(): void
    {
        $response = $this->postJson('/api/tokens/create', [
            'password' => 'password',
            'app_name' => 'PlateAPI',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_valid_to_create_token(): void
    {
        $response = $this->postJson('/api/tokens/create', [
            'email' => 'invalid-email',
            'password' => 'password',
            'app_name' => 'PlateAPI',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_is_required_to_create_token(): void
    {
        $response = $this->postJson('/api/tokens/create', [
            'email' => 'admin@example.com',
            'app_name' => 'PlateAPI',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_app_name_is_required_to_create_token(): void
    {
        $response = $this->postJson('/api/tokens/create', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['app_name']);
    }

    public function test_guest_cannot_list_sessions(): void
    {
        $response = $this->getJson('/api/tokens/sessions');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_sessions(): void
    {
        $user = User::factory()->create();

        $tokenA = $user->createToken('PlateAPI')->plainTextToken;
        $user->createToken('Mobile App');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/tokens/sessions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');

        $this->assertSame('PlateAPI', $response->json('data.0.name'));
        $this->assertSame('Mobile App', $response->json('data.1.name'));
    }

    public function test_guest_cannot_destroy_current_token(): void
    {
        $response = $this->deleteJson('/api/tokens/destroy');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_destroy_current_token(): void
    {
        $user = User::factory()->create();

        $currentToken = $user->createToken('Current App')->plainTextToken;
        $user->createToken('Other App');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $response = $this->withHeader('Authorization', 'Bearer ' . $currentToken)
            ->deleteJson('/api/tokens/destroy');

        $response->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'Current App',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'Other App',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'DELETE',
            'route' => 'api/tokens/destroy',
            'email' => $user->email,
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_cannot_destroy_all_tokens(): void
    {
        $response = $this->deleteJson('/api/tokens/destroy/all');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_destroy_all_tokens(): void
    {
        $user = User::factory()->create();

        $currentToken = $user->createToken('Current App')->plainTextToken;
        $user->createToken('Mobile App');
        $user->createToken('Desktop App');

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $response = $this->withHeader('Authorization', 'Bearer ' . $currentToken)
            ->deleteJson('/api/tokens/destroy/all');

        $response->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'DELETE',
            'route' => 'api/tokens/destroy/all',
            'email' => $user->email,
            'user_id' => $user->id,
        ]);
    }
}
