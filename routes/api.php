<?php

use App\Http\Controllers\ApiLogsController;
use App\Http\Controllers\PlateController;
use App\Models\ApiLogs;
use App\Models\Plate;
use App\Models\PlateTransfersHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

Route::post('/tokens/create', function (Request $request) {
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
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/tokens/destroy')->group(function () {
        Route::delete('', function (Request $request) {
            ApiLogsController::addLog($request);
            Auth::user()->currentAccessToken()->delete();
        });

        Route::delete('/all', function (Request $request) {
            ApiLogsController::addLog($request);
            Auth::user()->tokens()->delete();
        });
    });

    Route::prefix('/account')->group(function () {
        Route::get('/', function (Request $request) {
            ApiLogsController::addLog($request);
            return Auth::user();
        });
        Route::get('/actions', function (Request $request) {
            ApiLogsController::addLog($request);
            return Auth::user()->apiLogs()->latest()->get();
        });
        Route::patch('/update', function (Request $request) {
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
        });
    });

    Route::prefix('/plates')->group(function () {
        Route::get('/all', function (Request $request) {
            ApiLogsController::addLog($request);
            return Plate::where('user_id', Auth::id())->get();
        });

        Route::post('/create', function (Request $request) {
            ApiLogsController::addLog($request);
            return Plate::create([]);
        });

        Route::patch('/transfer', function (Request $request) {
            ApiLogsController::addLog($request);
            $request->validate([
                'plate_id' => 'required|integer|exists:plates,id',
                'to_user_id' => 'required|integer|exists:users,id',
            ]);
            Plate::where('user_id', Auth::id())->find($request->plate_id)->update(['user_id' => $request->to_user_id]);
            PlateTransfersHistory::create([
                'plate_id' => $request->plate_id,
                'to_user_id' => $request->to_user_id,
            ]);
        });

        Route::get('/transfer/{plate}/history', function (Request $request, Plate $plate) {
            ApiLogsController::addLog($request);
            abort_if($plate->user_id !== Auth::id(), 403);
            return $plate->plateTransfersHistory()->latest('transferred_at')->get();
        });

        Route::get('/{plate}', function (Request $request, Plate $plate) {
            ApiLogsController::addLog($request);
            abort_if($plate->user_id !== Auth::id(), 403);
            return $plate;
        });
    });
});


