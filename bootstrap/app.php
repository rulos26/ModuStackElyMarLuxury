<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
        ->withMiddleware(function (Middleware $middleware) {
            // Registrar middleware de intentos de login
            $middleware->alias([
                'login.attempts' => \App\Http\Middleware\LoginAttemptsMiddleware::class,
                'ip.access' => \App\Http\Middleware\IpAccessMiddleware::class,
            ]);
        })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
