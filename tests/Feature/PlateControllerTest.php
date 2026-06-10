<?php

namespace Tests\Feature;

use App\Models\ApiLogs;
use App\Models\Plate;
use App\Models\PlateTransfersHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlateControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createPlateFor(User $user, array $attributes = []): Plate
    {
        return Model::withoutEvents(function () use ($user, $attributes) {
            return Plate::factory()->create(array_merge([
                'user_id' => $user->id,
            ], $attributes));
        });
    }

    private function createTransferHistory(array $attributes = []): PlateTransfersHistory
    {
        return Model::withoutEvents(function () use ($attributes) {
            return PlateTransfersHistory::factory()->create($attributes);
        });
    }

    public function test_guest_cannot_list_plates(): void
    {
        $response = $this->getJson('/api/plates/all');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_only_his_plates(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $otherUser = User::factory()->create();

        $this->createPlateFor($user, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $this->createPlateFor($user, [
            'license_plate_number' => 'AA-002-AA',
        ]);

        $this->createPlateFor($otherUser, [
            'license_plate_number' => 'AA-003-AA',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/plates/all');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'license_plate_number',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'route' => 'api/plates/all',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_plates_list_is_paginated_by_ten(): void
    {
        $user = User::factory()->create();

        Model::withoutEvents(function () use ($user) {
            for ($i = 1; $i <= 15; $i++) {
                Plate::factory()->create([
                    'user_id' => $user->id,
                    'license_plate_number' => 'AA-' . str_pad($i, 3, '0', STR_PAD_LEFT) . '-AA',
                ]);
            }
        });

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/plates/all');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_guest_cannot_create_plate(): void
    {
        $response = $this->postJson('/api/plates/create');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_plate(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/plates/create');

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'license_plate_number',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.license_plate_number', 'AA-001-AA');

        $this->assertDatabaseHas('plates', [
            'user_id' => $user->id,
            'license_plate_number' => 'AA-001-AA',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'POST',
            'route' => 'api/plates/create',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_created_plate_number_is_incremented(): void
    {
        $user = User::factory()->create();

        $this->createPlateFor($user, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/plates/create');

        $response->assertCreated()
            ->assertJsonPath('data.license_plate_number', 'AA-002-AA');

        $this->assertDatabaseHas('plates', [
            'user_id' => $user->id,
            'license_plate_number' => 'AA-002-AA',
        ]);
    }

    public function test_guest_cannot_show_plate(): void
    {
        $user = User::factory()->create();

        $plate = $this->createPlateFor($user, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $response = $this->getJson('/api/plates/' . $plate->id);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_show_his_plate(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $plate = $this->createPlateFor($user, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/plates/' . $plate->id);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $plate->id,
                    'license_plate_number' => 'AA-001-AA',
                ],
            ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'route' => 'api/plates/' . $plate->id,
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_cannot_show_plate_of_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plate = $this->createPlateFor($otherUser, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/plates/' . $plate->id);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Plate not found',
            ]);
    }

    public function test_guest_cannot_transfer_plate(): void
    {
        $response = $this->patchJson('/api/plates/transfer', [
            'plate_id' => 1,
            'to_user_id' => 2,
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_transfer_his_plate(): void
    {
        $fromUser = User::factory()->create([
            'email' => 'from@example.com',
        ]);

        $toUser = User::factory()->create([
            'email' => 'to@example.com',
        ]);

        $plate = $this->createPlateFor($fromUser, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $token = $fromUser->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/plates/transfer', [
                'plate_id' => $plate->id,
                'to_user_id' => $toUser->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Plate transferred successfully',
                'plate_id' => $plate->id,
                'license_plate_number' => 'AA-001-AA',
            ]);

        $this->assertDatabaseHas('plates', [
            'id' => $plate->id,
            'user_id' => $toUser->id,
        ]);

        $this->assertDatabaseHas('plate_transfers_histories', [
            'plate_id' => $plate->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
        ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'PATCH',
            'route' => 'api/plates/transfer',
            'email' => 'from@example.com',
            'user_id' => $fromUser->id,
        ]);
    }

    public function test_authenticated_user_cannot_transfer_plate_of_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $toUser = User::factory()->create();

        $plate = $this->createPlateFor($otherUser, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/plates/transfer', [
                'plate_id' => $plate->id,
                'to_user_id' => $toUser->id,
            ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Plate not found',
            ]);

        $this->assertDatabaseHas('plates', [
            'id' => $plate->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_plate_id_is_required_to_transfer_plate(): void
    {
        $user = User::factory()->create();
        $toUser = User::factory()->create();

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/plates/transfer', [
                'to_user_id' => $toUser->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['plate_id']);
    }

    public function test_to_user_id_is_required_to_transfer_plate(): void
    {
        $user = User::factory()->create();

        $plate = $this->createPlateFor($user, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/plates/transfer', [
                'plate_id' => $plate->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['to_user_id']);
    }

    public function test_guest_cannot_see_transfer_history(): void
    {
        $user = User::factory()->create();

        $plate = $this->createPlateFor($user, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $response = $this->getJson('/api/plates/transfer/' . $plate->id . '/history');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_see_transfer_history_of_his_plate(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
        ]);

        $fromUser = User::factory()->create([
            'email' => 'from@example.com',
        ]);

        $toUser = User::factory()->create([
            'email' => 'to@example.com',
        ]);

        $plate = $this->createPlateFor($owner, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $this->createTransferHistory([
            'plate_id' => $plate->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'transferred_at' => now(),
        ]);

        $token = $owner->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/plates/transfer/' . $plate->id . '/history');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'plate',
                        'from_user',
                        'to_user',
                        'transferred_at',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'route' => 'api/plates/transfer/' . $plate->id . '/history',
            'email' => 'owner@example.com',
            'user_id' => $owner->id,
        ]);
    }

    public function test_authenticated_user_cannot_see_transfer_history_of_plate_of_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plate = $this->createPlateFor($otherUser, [
            'license_plate_number' => 'AA-001-AA',
        ]);

        $token = $user->createToken('PlateAPI')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/plates/transfer/' . $plate->id . '/history');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Plate not found',
            ]);
    }
}
