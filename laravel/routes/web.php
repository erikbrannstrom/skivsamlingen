<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Test page for SharedAuth middleware verification
Route::get('/auth-test', function () {
    return view('auth-test');
});
