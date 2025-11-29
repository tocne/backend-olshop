<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductSizeController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

// AUTH
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ADMIN ORDER LIST
Route::get('/admin/orders', [OrderController::class, 'adminIndex']);

// RESOURCES
Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('orders', OrderController::class);
Route::apiResource('series', SeriesController::class);

// PRODUCT SIZE
Route::post('/products/add-size-stock', [ProductController::class, 'addSizeStock']);

// ORDER SPECIAL ROUTES
Route::get('/orders/code/{order_code}', [OrderController::class, 'showByCode']);
Route::put('/admin/orders/{id}/cancel', [OrderController::class, 'cancel']);
Route::put('/admin/orders/{id}/pay', [OrderController::class, 'markAsPaid']);
Route::put('/admin/orders/{id}/ship', [OrderController::class, 'ship']);
Route::put('/admin/orders/{id}/complete', [OrderController::class, 'complete']);

// PAYMENT
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);

// FILE UPLOAD
Route::post('/upload', [UploadController::class, 'store']);

// CHECKOUT
Route::post('/checkout', [CheckoutController::class, 'checkout']);

// PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
