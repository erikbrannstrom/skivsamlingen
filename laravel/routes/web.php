<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

// Home routes
Route::get('/', [HomeController::class, 'index']);
Route::get('/about', [HomeController::class, 'about']);

// Test page for SharedAuth middleware verification
Route::get('/auth-test', function () {
    return view('auth-test');
});

// News routes
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/rss', [NewsController::class, 'rss']);
