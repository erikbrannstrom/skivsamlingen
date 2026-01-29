<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UsersController;
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

// Users routes
Route::match(['get', 'post'], '/users/search', [UsersController::class, 'search']);
Route::get('/users/{username}/export', [UsersController::class, 'export']);
Route::get('/users/{username}/print', [UsersController::class, 'printview']);

// Legacy URL redirect: /users/username/offset/order/direction -> query string format
Route::get('/users/{username}/{offset}/{order}/{direction}', function (string $username, int $offset, string $order, string $direction) {
    return redirect("/users/{$username}?offset={$offset}&order={$order}&dir={$direction}", 302);
})->where([
    'offset' => '[0-9]+',
    'order' => 'artist|format|year',
    'direction' => 'asc|desc',
]);

// Also handle partial legacy URLs (just offset, or offset+order)
Route::get('/users/{username}/{offset}/{order}', function (string $username, int $offset, string $order) {
    return redirect("/users/{$username}?offset={$offset}&order={$order}", 302);
})->where([
    'offset' => '[0-9]+',
    'order' => 'artist|format|year',
]);

Route::get('/users/{username}/{offset}', function (string $username, int $offset) {
    return redirect("/users/{$username}?offset={$offset}", 302);
})->where('offset', '[0-9]+');

Route::get('/users/{username}', [UsersController::class, 'profile']);

// Account routes
Route::get('/account/login', [AccountController::class, 'showLogin']);
Route::post('/account/login', [AccountController::class, 'login']);
Route::get('/account/logout', [AccountController::class, 'logout']);
Route::get('/account/register', [AccountController::class, 'showRegister']);
Route::post('/account/register', [AccountController::class, 'register']);
Route::get('/account/forgot', [AccountController::class, 'showForgot']);
Route::post('/account/forgot', [AccountController::class, 'forgot']);
Route::get('/account/recover/{username}/{hash}', [AccountController::class, 'showRecover']);
Route::post('/account/recover/{username}/{hash}', [AccountController::class, 'recover']);
Route::get('/account/edit', [AccountController::class, 'edit']);
Route::post('/account/edit', [AccountController::class, 'update']);
Route::post('/account/password', [AccountController::class, 'password']);
Route::post('/account/unregister', [AccountController::class, 'unregister']);
