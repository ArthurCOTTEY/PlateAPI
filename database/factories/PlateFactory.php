<?php

namespace Database\Factories;

use App\Http\Controllers\PlateController;
use App\Models\Plate;
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
            'license_plate_number' => PlateController::generate(),
            'user_id' => 1
        ];
    }
}
