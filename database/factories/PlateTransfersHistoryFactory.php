<?php

namespace Database\Factories;

use App\Models\Plate;
use App\Models\PlateTransfersHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlateTransfersHistoryFactory extends Factory
{
    protected $model = PlateTransfersHistory::class;

    public function definition(): array
    {
        $fromUser = User::factory();
        $toUser = User::factory();

        return [
            'from_user_id' => $fromUser,
            'to_user_id' => $toUser,
            'plate_id' => Plate::factory(),
            'transferred_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
