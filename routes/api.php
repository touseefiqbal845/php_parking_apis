<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LoginController;
// use App\Http\Controllers\API\LotNumberController;
use App\Http\Controllers\API\LotController;
use App\Http\Controllers\Api\AccessCodeController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\VehicleManagementController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/registerPermit', [RegistrationController::class, 'registerPermit']);
Route::post('/vehicleHistory', [RegistrationController::class, 'vehicleHistory']);
Route::post('/vehicle/activate', [RegistrationController::class, 'registerPermitProcess']);
Route::post('/vehicle/activate/email', [RegistrationController::class, 'shareVehicleInfo']);

Route::post('/login', [LoginController::class, 'login']);
Route::get('/logout', [LoginController::class, 'logout']);
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
    Route::delete('/', [UserController::class, 'bulkDelete']);
});


Route::prefix('lots')->group(function () {
    Route::get('/', [UserController::class, 'lot_index']);
    Route::post('/', [UserController::class, 'lot_store']);
    Route::get('/{lotNumber}', [UserController::class, 'lot_show']);
    Route::put('/{lotNumber}', [UserController::class, 'lot_update']);
    Route::delete('/{id}', [UserController::class, 'lot_destroy']);
    Route::post('/export', [UserController::class, 'lot_export']);
});

Route::prefix('access-codes')->group(function () {
    Route::get('/', [AccessCodeController::class, 'index']);
    Route::post('/bulk', [AccessCodeController::class, 'bulkStore']);
    Route::get('/{accessCode}', [AccessCodeController::class, 'show']);
    Route::put('/{accessCode}', [AccessCodeController::class, 'update']);
    Route::delete('/{id}', [AccessCodeController::class, 'destroy']);
    Route::put('/toggle-active', [AccessCodeController::class, 'toggleActive']);
});

Route::prefix('vehicles')->group(function () {
    Route::get('/', [VehicleManagementController::class, 'index']);
    Route::post('/bulk', [VehicleManagementController::class, 'bulkStore']);
    Route::get('/{vehicle}', [VehicleManagementController::class, 'show']);
    Route::put('/{vehicle}', [VehicleManagementController::class, 'update']);
    Route::delete('/{id}', [VehicleManagementController::class, 'destroy']);
});
