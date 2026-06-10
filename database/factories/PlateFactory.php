<?php

namespace Database\Factories;

use App\Http\Controllers\PlateController;
use App\Models\Plate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plate>
 */
class PlateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_plate_number' => strtoupper(fake()->unique()->bothify('??-###-??')),
            'user_id' => User::factory(),
        ];
    }
}
