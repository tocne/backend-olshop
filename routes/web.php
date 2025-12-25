<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return 'login page placeholder';
})->name('login');

Route::get('/register', function () {
    return 'register page placeholder';
})->name('register');

/*
|--------------------------------------------------------------------------
| ADMIN PANEL (DISAMARKAN)
|--------------------------------------------------------------------------
*/
Route::prefix(config('admin.path'))
    ->middleware(['auth', 'is_admin'])
    ->group(function () {
        Route::get('/dashboard', function () {
            return 'admin dashboard';
        });
    });
