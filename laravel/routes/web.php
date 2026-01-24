<?php

use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Test page for SharedAuth middleware verification
Route::get('/auth-test', function () {
    return view('auth-test');
});

// News routes
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/rss', [NewsController::class, 'rss']);
