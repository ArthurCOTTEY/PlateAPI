<?php

namespace Tests\Unit;

use App\Http\Controllers\ApiLogsController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ApiLogsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_add_log_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        Auth::login($user);

        $request = Request::create('/api/account', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        ApiLogsController::addLog($request);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'route' => 'api/account',
            'ip' => '127.0.0.1',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_it_can_add_log_with_email_and_user_id_when_user_is_not_authenticated(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        Auth::logout();

        $request = Request::create('/api/tokens/create', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        ApiLogsController::addLog($request, $user->email, $user->id);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'POST',
            'route' => 'api/tokens/create',
            'ip' => '127.0.0.1',
            'email' => 'admin@example.com',
            'user_id' => $user->id,
        ]);
    }
}
