<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomSeriesController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImportController;
use App\Http\Controllers\Api\ProductSizeController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Api\UploadController;

/*
|--------------------------------------------------------------------------
| AUTH (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PUBLIC / GUEST (CUSTOMER)
|--------------------------------------------------------------------------
*/
Route::get('/orders/active', [OrderController::class, 'active']);
Route::post('/orders', [OrderController::class, 'store']); // checkout
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::get('/orders/code/{orderCode}', [OrderController::class, 'showByCode']);

Route::post('/payments', [PaymentController::class, 'store']);
Route::post('/payments/{payment}/upload-proof', [PaymentController::class, 'uploadProof']);
Route::post('/orders/{orderCode}/upload-proof', [OrderController::class, 'uploadProof']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER (LOGIN SAJA)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

});

/*
|--------------------------------------------------------------------------
| ADMIN ONLY 🔐 (auth:sanctum + is_admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {

    /*
    |-----------------------
    | DASHBOARD
    |-----------------------
    */
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/sales-weekly', [DashboardController::class, 'salesWeekly']);

    /*
    |-----------------------
    | BANNERS
    |-----------------------
    */
    Route::get('/banners', [BannerController::class, 'index']);

    /*
    |-----------------------
    | ORDERS (ADMIN ACTION)
    |-----------------------
    */
    Route::get('/orders/admin', [OrderController::class, 'adminIndex']);
    Route::get('/orders/admin/{id}', [OrderController::class, 'show']);

    Route::put('/orders/{id}/pay', [OrderController::class, 'markAsPaid']);
    Route::put('/orders/{id}/ship', [OrderController::class, 'ship']);
    Route::put('/orders/{id}/complete', [OrderController::class, 'complete']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    /*
    |-----------------------
    | CUSTOM SERIES
    |-----------------------
    */
    Route::prefix('series/custom')->group(function () {
        Route::get('/', [CustomSeriesController::class, 'index']);
        Route::get('/{id}', [CustomSeriesController::class, 'show']);
        Route::post('/', [CustomSeriesController::class, 'store']);
    });

    /*
    |-----------------------
    | PRODUCT IMPORT
    |-----------------------
    */
    Route::post('/imports/products', [ProductImportController::class, 'import']);

    /*
    |-----------------------
    | PRODUCTS
    |-----------------------
    */
    Route::post('/products/add-size-stock', [ProductController::class, 'addSizeStock']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::apiResource('products', ProductController::class);

    /*
    |-----------------------
    | PRODUCT SIZES
    |-----------------------
    */
    Route::post('/product-sizes', [ProductSizeController::class, 'store']);
    Route::put('/product-sizes/{id}', [ProductSizeController::class, 'update']);
    Route::delete('/product-sizes/{id}', [ProductSizeController::class, 'destroy']);

    /*
    |-----------------------
    | SERIES
    |-----------------------
    */
    Route::get('/series/by-product/{productId}', [SeriesController::class, 'byProduct']);
    Route::delete('/series/{id}', [SeriesController::class, 'destroy']);
    Route::delete('/series', [SeriesController::class, 'destroyAll']);
    Route::apiResource('series', SeriesController::class);

    /*
    |-----------------------
    | CATEGORIES
    |-----------------------
    */
    Route::apiResource('categories', CategoryController::class);

    /*
    |-----------------------
    | UPLOAD
    |-----------------------
    */
    Route::post('/upload', [UploadController::class, 'store']);

    /*
    |-----------------------
    | PAYMENTS (ADMIN VIEW)
    |-----------------------
    */
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| PUBLIC PRODUCTS (VIEW ONLY)
|--------------------------------------------------------------------------
*/
Route::get('/products/category/{categoryPrefix}', [ProductController::class, 'getProductsByCategory']);
Route::get('/public/products', [ProductController::class, 'publicIndex']);
Route::get('/public/series', [SeriesController::class, 'publicIndex']);
Route::get('/public/banners', [BannerController::class, 'index']);
