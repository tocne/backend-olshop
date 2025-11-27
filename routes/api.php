<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductSizeController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Tes endpoint bawaan

Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('orders', OrderController::class);
Route::apiResource('series', SeriesController::class);

// Product detail
Route::get('/products/{id}', [ProductController::class, 'show']);

// Delete product
Route::post('/products/add-size-stock', [ProductController::class, 'addSizeStock']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

// Additional routes for product sizes
Route::post('/product-sizes', [ProductSizeController::class, 'store']);
Route::put('/product-sizes/{id}', [ProductSizeController::class, 'update']);
Route::delete('/product-sizes/{id}', [ProductSizeController::class, 'destroy']);

Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);
Route::put('/orders/{id}/ship', [OrderController::class, 'ship']);
Route::put('/orders/{id}/complete', [OrderController::class, 'complete']);
Route::post('/upload', [UploadController::class, 'store']);
Route::post('/checkout', [CheckoutController::class, 'checkout']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route untuk API utama
});
