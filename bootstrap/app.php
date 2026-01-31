<?php

use App\Http\Middleware\SharedAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude CodeIgniter cookies from Laravel's encryption
        $middleware->encryptCookies(except: [
            'ci_session',
            'skiv_remember',
        ]);

        // Add SharedAuth middleware to read CodeIgniter session cookies
        $middleware->appendToGroup('web', SharedAuth::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
