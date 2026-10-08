<?php


use App\Http\Controllers\Api\V1\ManagerDashboardController;
use App\Http\Controllers\Api\V1\DailyStationReportController;
use App\Http\Controllers\Api\V1\ShiftAuditPackageController;
use App\Http\Controllers\Api\V1\ShiftClosingController;
use App\Http\Controllers\Api\V1\ReconciliationController;
use App\Http\Controllers\Api\V1\ShiftLedgerController;
use App\Http\Controllers\Api\V1\TraditionalTransactionController;
use App\Http\Controllers\Api\V1\StationApprovalController;
use App\Http\Controllers\Api\V1\MeterReadingController;
use App\Http\Controllers\Api\V1\TransactionReceiptController;
use App\Http\Controllers\Api\V1\ShiftCustodyController;
use App\Http\Controllers\Api\V1\ShiftActivationController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\ShiftAssignmentController;
use App\Http\Controllers\Api\V1\ShiftNozzleAssignmentController;
use App\Http\Controllers\Api\V1\ShiftTemplateController;
use App\Http\Controllers\Api\V1\StationStaffController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FuelPriceController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NozzleController;
use App\Http\Controllers\Api\V1\PumpController;
use App\Http\Controllers\Api\V1\StationController;
use App\Http\Controllers\Api\V1\TankController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ReconciliationReviewController;
use App\Http\Controllers\Api\V1\ShiftCloseController;


Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/health',
        [HealthController::class, 'index']
    );

    Route::post(
        '/auth/login',
        [AuthController::class, 'login']
    );


    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {


        Route::get(
            '/me',
            [AuthController::class, 'me']
        );


        Route::post(
            '/auth/logout',
            [AuthController::class, 'logout']
        );

        Route::get(
                '/transactions/{transaction}/receipt',
                [TransactionReceiptController::class, 'show']
            );

        Route::get(
            '/shifts/{shift}/audit-package',
            [ShiftAuditPackageController::class, 'show']
            );

        Route::get(
            '/shifts/{shift}/audit-package/verify',
            [ShiftAuditPackageController::class, 'verify']
            );
        
        Route::get(
                '/stations/{station}/reports/daily',
                [DailyStationReportController::class, 'show']
            );

        Route::get(
            '/stations/{station}/dashboard',
            [ManagerDashboardController::class, 'show']
            );        

        /*
        |--------------------------------------------------------------------------
        | Temporary RBAC Tests
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
        | Station Owner / Platform Admin
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'role:STATION_OWNER,PLATFORM_ADMIN'
        )->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Station Configuration
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Tanks
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stations/{station}/tanks',
                [TankController::class, 'index']
            );

            Route::post(
                '/stations/{station}/tanks',
                [TankController::class, 'store']
            );


            /*
            |--------------------------------------------------------------------------
            | Fuel Prices
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stations/{station}/prices',
                [FuelPriceController::class, 'index']
            );

            Route::get(
                '/stations/{station}/prices/current',
                [FuelPriceController::class, 'current']
            );

            Route::post(
                '/stations/{station}/prices',
                [FuelPriceController::class, 'store']
            );

            /*
            |--------------------------------------------------------------------------
            | Shift Templates
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stations/{station}/shift-templates',
                [ShiftTemplateController::class, 'index']
            );

            Route::post(
                '/stations/{station}/shift-templates',
                [ShiftTemplateController::class, 'store']
            );

            Route::patch(
                '/shift-templates/{shiftTemplate}',
                [ShiftTemplateController::class, 'update']
            );


            /*
            |--------------------------------------------------------------------------
            | Station Staff
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stations/{station}/staff',
                [StationStaffController::class, 'index']
            );

            Route::post(
                '/stations/{station}/staff',
                [StationStaffController::class, 'store']
            );

            Route::patch(
                '/station-staff/{stationStaff}/deactivate',
                [StationStaffController::class, 'deactivate']
            );

        });

        /*
        |--------------------------------------------------------------------------
        | Platform Admin Governance
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:PLATFORM_ADMIN')->group(function () {
            Route::post(
                '/admin/stations/{station}/approve',
                [StationApprovalController::class, 'approve']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Station Manager - Shift Operations
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:STATION_MANAGER')->group(function () {
            Route::get(
                '/stations/{station}/shifts',
                [ShiftController::class, 'index']
            );

            Route::post(
                '/stations/{station}/shifts',
                [ShiftController::class, 'store']
            );

            Route::get(
                '/shifts/{shift}',
                [ShiftController::class, 'show']
            );

            Route::post(
                '/shifts/{shift}/attendants',
                [ShiftAssignmentController::class, 'store']
            );

            Route::post(
                '/shifts/{shift}/nozzles',
                [ShiftNozzleAssignmentController::class, 'store']
            );

            Route::post(
                '/shifts/{shift}/opening-readings',
                [MeterReadingController::class, 'storeOpening']
            );

            Route::post(
                '/shifts/{shift}/activate',
                [ShiftActivationController::class, 'activate']
            );

            Route::get(
                '/shifts/{shift}/transactions',
                [TraditionalTransactionController::class, 'index']
            );

            Route::get(
                '/shifts/{shift}/ledger',
                [ShiftLedgerController::class, 'summary']
            );

            Route::post(
                '/shifts/{shift}/closing-readings',
                [ShiftClosingController::class, 'storeClosingReading']
            );

            Route::post(
                '/shifts/{shift}/reconcile',
                [ReconciliationController::class, 'generate']
            );


    /*
|--------------------------------------------------------------------------
| Reconciliation Review
|--------------------------------------------------------------------------
*/

Route::post(
    '/shifts/{shift}/reconciliation/review',
    [ReconciliationReviewController::class, 'review']
);

Route::post(
    '/shift-discrepancies/{discrepancy}/resolve',
    [
        ReconciliationReviewController::class,
        'resolveDiscrepancy'
    ]
);

Route::post(
    '/shifts/{shift}/reconciliation/approve-resolved',
    [
        ReconciliationReviewController::class,
        'approveAfterResolution'
    ]
);


/*
|--------------------------------------------------------------------------
| Shift Close + Audit Seal
|--------------------------------------------------------------------------
*/

Route::post(
    '/shifts/{shift}/close',
    [ShiftCloseController::class, 'close']
);

        });

        /*
        |--------------------------------------------------------------------------
        | Attendant - Custody Acceptance
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:ATTENDANT')->group(function () {
            Route::post(
                '/shift-nozzle-assignments/{assignment}/accept',
                [ShiftCustodyController::class, 'accept']
            );

            Route::post(
                '/shifts/{shift}/transactions/traditional',
                [TraditionalTransactionController::class, 'store']
            );
            
            Route::post(
                '/shifts/{shift}/cash-declaration',
                [ShiftClosingController::class, 'declareCash']
            );

        });

    });

});