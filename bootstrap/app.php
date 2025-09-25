<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
        ->withMiddleware(function (Middleware $middleware) {
            // Registrar middleware de intentos de login
            $middleware->alias([
                'login.attempts' => \App\Http\Middleware\LoginAttemptsMiddleware::class,
                'ip.access' => \App\Http\Middleware\IpAccessMiddleware::class,
                'maintenance' => \App\Http\Middleware\MaintenanceModeMiddleware::class,
                'dynamic.driver' => \App\Http\Middleware\DynamicDriverMiddleware::class,
                'api.auth' => \App\Http\Middleware\ApiAuthMiddleware::class,
                'api.rate.limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
                'system.integration' => \App\Http\Middleware\SystemIntegrationMiddleware::class,
                'integrated.logging' => \App\Http\Middleware\IntegratedLoggingMiddleware::class,
                'performance.monitoring' => \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
                'integrated.security' => \App\Http\Middleware\IntegratedSecurityMiddleware::class,
                'theme' => \App\Http\Middleware\ThemeMiddleware::class,
                'adminlte' => \App\Http\Middleware\AdminLteMiddleware::class,
                'adminlte.menu' => \App\Http\Middleware\AdminLteMenuMiddleware::class,
                'update.logo' => \App\Http\Middleware\UpdateLogoMiddleware::class,
            ]);

            // Aplicar middleware de mantenimiento globalmente
            $middleware->web(append: [
                \App\Http\Middleware\MaintenanceModeMiddleware::class,
                \App\Http\Middleware\ThemeMiddleware::class,
                \App\Http\Middleware\UpdateLogoMiddleware::class,
                \App\Http\Middleware\AdminLteMiddleware::class,
                \App\Http\Middleware\AdminLteMenuMiddleware::class,
            ]);
        })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
