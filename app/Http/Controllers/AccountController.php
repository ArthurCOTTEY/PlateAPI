<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ApiLogs;

class AccountController extends Controller
{
    public function index(Request $request) {
        ApiLogsController::addLog($request);
        return Auth::user();
    }

    public function actions(Request $request) {
        ApiLogsController::addLog($request);
        return Auth::user()->apiLogs()->latest()->get();
    }

    public function update(Request $request) {
        $request->validate([
            'username' => 'min:1',
            'email' => 'email',
            'password' => 'min:8',
        ]);
        $user = Auth::user();
        if (isset($request->username) && $request->username !== $user->username) {
            $user->username = $request->username;
        }
        if (isset($request->email) && $request->email !== $user->email) {
            $user->email = $request->email;
        }
        if (isset($request->password)) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        if (isset($request->email) && $request->email !== $user->email) {
            ApiLogs::where('user_id', Auth::id())->update(['email' => $request->email]);
        }
        ApiLogsController::addLog($request);
    }
}
