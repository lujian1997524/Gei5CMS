<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
            Route::middleware('web')
                ->group(base_path('routes/install.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 添加全局安装检查中间件 - 使用 prepend 确保最先执行
        $middleware->web(prepend: [\App\Http\Middleware\CheckInstallation::class]);
        
        // 替换默认的 CSRF 中间件
        $middleware->replace(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, \App\Http\Middleware\VerifyCsrfToken::class);
        
        $middleware->alias([
            'admin.permission' => \App\Http\Middleware\AdminPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
