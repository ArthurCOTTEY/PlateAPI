<?php

namespace Database\Seeders;

use App\Http\Controllers\PlateController;
use App\Models\ApiLogs;
use App\Models\Plate;
use App\Models\PlateTransfersHistory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $users = User::factory()
            ->count(10)
            ->create();

        $allUsers = $users->push($admin);

        foreach ($allUsers as $user) {
            Plate::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create([
                    'user_id' => $user->id,
                ]);
        }

        $plates = Plate::with('user')->get();

        foreach ($plates as $plate) {
            $transferCount = fake()->numberBetween(0, 2);

            for ($i = 0; $i < $transferCount; $i++) {
                $fromUserId = $plate->user_id;

                $toUser = $allUsers
                    ->where('id', '!=', $fromUserId)
                    ->random();

                PlateTransfersHistory::factory()->create([
                    'plate_id' => $plate->id,
                    'from_user_id' => $fromUserId,
                    'to_user_id' => $toUser->id,
                ]);

                $plate->update([
                    'user_id' => $toUser->id,
                ]);
            }
        }

        ApiLogs::factory()
            ->count(100)
            ->create();
    }
}
