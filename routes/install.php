<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installation Routes
|--------------------------------------------------------------------------
|
| 安装向导路由，只在未安装时可用
|
*/

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/step/{step}', [InstallController::class, 'step'])->name('step');
    
    // 安装POST路由需要排除CSRF验证但保留其他web中间件
    Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
        Route::post('/database', [InstallController::class, 'handleDatabaseConfig'])->name('database');
        Route::post('/admin', [InstallController::class, 'handleAdminConfig'])->name('admin');
        Route::post('/site', [InstallController::class, 'handleSiteConfig'])->name('site');
    });
    
    Route::get('/complete', [InstallController::class, 'step'])->name('complete');
});