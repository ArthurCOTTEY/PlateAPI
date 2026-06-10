<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiLogsResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ApiLogs;

class AccountController extends Controller
{
    public function show(Request $request) {
        ApiLogsController::addLog($request);
        return new userResource(Auth::user());
    }

    public function actions(Request $request) {
        ApiLogsController::addLog($request);
        return ApiLogsResource::collection(Auth::user()->apiLogs()->latest()->paginate(10));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|min:1',
            'email' => 'sometimes|email',
            'password' => 'sometimes|string|min:8',
        ]);

        $user = Auth::user();

        $emailChanged = $request->filled('email') && $request->email !== $user->email;

        if ($request->filled('name') && $request->name !== $user->name) {
            $user->name = $request->name;
        }

        if ($emailChanged) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($emailChanged) {
            ApiLogs::where('user_id', $user->id)->update([
                'email' => $user->email,
            ]);
        }

        ApiLogsController::addLog($request);

        return new UserResource($user);
    }
}
