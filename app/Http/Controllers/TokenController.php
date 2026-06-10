<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function create(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'app_name' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        ApiLogsController::addLog($request, $user->email, $user->id);
        return $user->createToken($request->app_name)->plainTextToken;
    }

    public function destroy(Request $request) {
        ApiLogsController::addLog($request);
        Auth::user()->currentAccessToken()->delete();
    }

    function destroyAll(Request $request) {
        ApiLogsController::addLog($request);
        Auth::user()->tokens()->delete();
    }
}
