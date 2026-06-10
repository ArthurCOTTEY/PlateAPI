<?php

namespace Database\Factories;

use App\Models\ApiLogs;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiLogsFactory extends Factory
{
    protected $model = ApiLogs::class;

    public function definition(): array
    {
        $user = User::query()->inRandomOrder()->first();

        return [
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'route' => fake()->randomElement([
                '/api/login',
                '/api/logout',
                '/api/user',
                '/api/plates',
                '/api/plates/transfer',
            ]),
            'ip' => fake()->ipv4(),
            'email' => $user?->email ?? fake()->safeEmail(),
            'user_id' => $user?->id,
        ];
    }
}
