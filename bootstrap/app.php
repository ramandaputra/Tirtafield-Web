<?php

// Suppress PHP 8.5 deprecation notices from third-party vendor libraries
error_reporting(E_ALL & ~E_DEPRECATED);

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
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'api/*.php',
            'login.php',
            'attendance.php',
            'approval.php',
            'employees.php',
            'offices.php',
            'bidang.php',
            'settings.php',
            'update_profile.php',
            'resolve_maps.php',
            '*.php',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
