<?php

namespace App\Http\Controllers;

use App\Models\ApiLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiLogsController extends Controller
{
    public static function addLog(Request $request, string $email = null, int $userId = null) {
        ApiLogs::create([
            'method' => $request->method(),
            'route' => $request->path(),
            'ip' => $request->ip(),
            'email' => Auth::user()->email ?? $email,
            'user_id' => Auth::id() ?? $userId,
        ]);
    }
}
