<?php

use App\Http\Controllers\Api\V1\CalculationController;
use App\Http\Middleware\AutoGuestAuthentication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request): JsonResponse {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware([AutoGuestAuthentication::class])->group(function (): void {
    Route::get('/calculations', [CalculationController::class, 'index']);           // Display a listing of calculations
    Route::post('/calculations', [CalculationController::class, 'store']);          // Store a newly created calculation in the database
    Route::delete('/calculations/{id}', [CalculationController::class, 'destroy']); // Remove the specified calculation from the database
    Route::delete('/calculations', [CalculationController::class, 'clear']);        // Remove all calculations from the database
});
