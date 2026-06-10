<?php

namespace App\Http\Controllers;

use App\Http\Resources\TokenResource;
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
        return response()->json([
            'token' => $user->createToken($request->app_name)->plainTextToken,
        ], 201);
    }

    public function sessions(Request $request) {
        return TokenResource::collection(Auth::user()->tokens()->get());
    }

    public function destroy(Request $request) {
        ApiLogsController::addLog($request);
        Auth::user()->currentAccessToken()->delete();
        return response()->noContent();
    }

    function destroyAll(Request $request) {
        ApiLogsController::addLog($request);
        Auth::user()->tokens()->delete();
        return response()->noContent();
    }
}
