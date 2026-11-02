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
    ->withMiddleware(function (Middleware $middleware): void {
        // Not logged in? Send to the admin login page.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        // Already logged in? Skip the login page and go to the dashboard.
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
