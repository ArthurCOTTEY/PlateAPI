<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\PlateController;
use App\Http\Controllers\TokenController;

Route::post('/tokens/create', [TokenController::class, 'create']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/tokens')->group(function () {
        Route::get('/sessions', [TokenController::class, 'sessions']);
        Route::prefix('/destroy')->group(function () {
            Route::delete('', [TokenController::class, 'destroy']);
            Route::delete('/all', [TokenController::class, 'destroyAll']);
        });
    });

    Route::prefix('/account')->group(function () {
        Route::get('/', [AccountController::class, 'show']);
        Route::get('/actions', [AccountController::class, 'actions']);
        Route::patch('/update', [AccountController::class, 'update']);
    });

    Route::prefix('/plates')->group(function () {
        Route::get('/all', [PlateController::class, 'all']);
        Route::post('/create', [PlateController::class, 'create']);
        Route::patch('/transfer', [PlateController::class, 'transfer']);
        Route::get('/transfer/{plate}/history', [PlateController::class, 'transferHistory']);
        Route::get('/{plate}', [PlateController::class, 'show']);
    });
});


