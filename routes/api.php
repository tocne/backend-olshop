<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductSizeController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Api\ProductImportController;
use App\Http\Controllers\Api\UploadController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| DASHBOARD (ADMIN)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
Route::get('/dashboard/sales-weekly', [DashboardController::class, 'salesWeekly']);

/*
|--------------------------------------------------------------------------
| ORDERS (CUSTOMER)
|--------------------------------------------------------------------------
*/
Route::post('/orders', [OrderController::class, 'store']);              // READY / PILSUK / SERI
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::get('/orders/code/{order_code}', [OrderController::class, 'showByCode']);

/*
|--------------------------------------------------------------------------
| ORDERS (ADMIN ACTIONS)
|--------------------------------------------------------------------------
*/
Route::get('/admin/orders', [OrderController::class, 'adminIndex']);

Route::put('/admin/orders/{id}/pay', [OrderController::class, 'markAsPaid']);
Route::put('/admin/orders/{id}/ship', [OrderController::class, 'ship']);
Route::put('/admin/orders/{id}/complete', [OrderController::class, 'complete']);
Route::put('/admin/orders/{id}/cancel', [OrderController::class, 'cancel']);

/*
|--------------------------------------------------------------------------
| PAYMENTS
|--------------------------------------------------------------------------
*/
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);

/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/
Route::get('/products/category/{categoryPrefix}', [ProductController::class, 'getProductsByCategory']);
Route::post('/products/add-size-stock', [ProductController::class, 'addSizeStock']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

Route::apiResource('products', ProductController::class);

/*
|--------------------------------------------------------------------------
| PRODUCT SIZES
|--------------------------------------------------------------------------
*/
Route::post('/product-sizes', [ProductSizeController::class, 'store']);
Route::put('/product-sizes/{id}', [ProductSizeController::class, 'update']);
Route::delete('/product-sizes/{id}', [ProductSizeController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| SERIES
|--------------------------------------------------------------------------
*/
Route::get('/series/by-product/{productId}', [SeriesController::class, 'byProduct']);
Route::delete('/series/{id}', [SeriesController::class, 'destroy']);
Route::delete('/series', [SeriesController::class, 'destroyAll']);

Route::apiResource('series', SeriesController::class);

/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/
Route::apiResource('categories', CategoryController::class);

/*
|--------------------------------------------------------------------------
| UPLOAD
|--------------------------------------------------------------------------
*/
Route::post('/upload', [UploadController::class, 'store']);

/*|--------------------------------------------------------------------------
| PRODUCT IMPORT
|--------------------------------------------------------------------------*/
Route::post('/products/import', [ProductImportController::class, 'import']);
