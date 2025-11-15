<?php

use Illuminate\Support\Facades\Route;

Route::get('/api/documentation', function () {
    return view('l5-swagger::index');
});

Route::get('/login', function () {
    return 'login page placeholder';
})->name('login');

Route::get('/register', function () {
    return 'register page placeholder';
})->name('register');

// Route::get('/', function () {
//     return view('welcome');
// });
