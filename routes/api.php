<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NozzleController;
use App\Http\Controllers\Api\V1\PumpController;
use App\Http\Controllers\Api\V1\StationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/health', [HealthController::class, 'index']);

    Route::post('/auth/login', [AuthController::class, 'login']);


    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/auth/logout', [AuthController::class, 'logout']);


        /*
        |--------------------------------------------------------------------------
        | Temporary RBAC Test Routes
        |--------------------------------------------------------------------------
        */

        Route::get('/test/owner', function () {
            return response()->json([
                'data' => [
                    'message' => 'Station Owner access confirmed.',
                ],
            ]);
        })->middleware('role:STATION_OWNER');


        Route::get('/test/manager', function () {
            return response()->json([
                'data' => [
                    'message' => 'Station Manager access confirmed.',
                ],
            ]);
        })->middleware('role:STATION_MANAGER');


        Route::get('/test/attendant', function () {
            return response()->json([
                'data' => [
                    'message' => 'Fuel Attendant access confirmed.',
                ],
            ]);
        })->middleware('role:ATTENDANT');


        Route::get('/test/admin', function () {
            return response()->json([
                'data' => [
                    'message' => 'Platform Admin access confirmed.',
                ],
            ]);
        })->middleware('role:PLATFORM_ADMIN');


        /*
        |--------------------------------------------------------------------------
        | Station Owner / Platform Admin Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'role:STATION_OWNER,PLATFORM_ADMIN'
        )->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Stations
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stations',
                [StationController::class, 'index']
            );

            Route::post(
                '/stations',
                [StationController::class, 'store']
            );

            Route::get(
                '/stations/{station}',
                [StationController::class, 'show']
            );

            Route::patch(
                '/stations/{station}',
                [StationController::class, 'update']
            );


            /*
            |--------------------------------------------------------------------------
            | Pumps
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stations/{station}/pumps',
                [PumpController::class, 'index']
            );

            Route::post(
                '/stations/{station}/pumps',
                [PumpController::class, 'store']
            );


            /*
            |--------------------------------------------------------------------------
            | Nozzles
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/pumps/{pump}/nozzles',
                [NozzleController::class, 'store']
            );

        });

    });

});