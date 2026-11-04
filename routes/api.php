<?php
use App\Http\Controllers\Api\DeliveryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;


// Deliveries API endpoints (/api/deliveries)
Route::apiResource('deliveries', DeliveryController::class);

// Custom endpoint for drivers to accept an order
Route::post('deliveries/{id}/accept', [DeliveryController::class, 'accept']);

Route::post('deliveries/{id}/complete', [DeliveryController::class, 'complete']);

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
});