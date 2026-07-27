<?php

use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->redirectGuestsTo(
            fn (Request $request) => match (true) {
                $request->is('admin') || $request->is('admin/*') => Auth::guard('web')->check()
                    ? route('dashboard')
                    : route('admin.login'),
                Auth::guard('admin')->check() => route('admin.dashboard'),
                default => route('login'),
            },
        );
        $middleware->redirectUsersTo(
            fn () => Auth::guard('admin')->check()
                ? route('admin.dashboard')
                : route('dashboard'),
        );
        $middleware->alias([
            'admin.permission' => EnsureAdminPermission::class,
            'permission' => EnsurePermission::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
