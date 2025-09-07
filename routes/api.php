<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

// API健康检查
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'version' => config('app.version', '1.0.0'),
        'timestamp' => now()->toISOString(),
        'system' => [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]
    ]);
})->name('api.health');

// API信息端点
Route::get('info', function () {
    return response()->json([
        'api_version' => 'v1',
        'name' => config('app.name'),
        'description' => 'Gei5CMS RESTful API',
        'documentation' => url('/api/docs'),
        'contact' => [
            'support' => 'support@gei5cms.com',
            'website' => 'https://gei5cms.com'
        ],
        'rate_limits' => [
            'authenticated' => '1000 requests per hour',
            'guest' => '100 requests per hour'
        ]
    ]);
})->name('api.info');

// API版本 v1 路由组
Route::prefix('v1')->name('v1.')->group(function () {
    
    // 公开端点（无需认证）
    Route::prefix('public')->name('public.')->group(function () {
        // 公开内容API（由主题提供）
        Route::get('content', function () {
            return response()->json([
                'message' => 'Public content API endpoints are provided by active theme',
                'available_endpoints' => []
            ]);
        })->name('content.index');
        
        // 公开设置API
        Route::get('settings/public', function () {
            // 只返回公开的系统设置
            $publicSettings = [
                'site_name' => 'Gei5CMS',
                'site_description' => 'Modern Web Application Framework',
                'timezone' => 'UTC',
                'language' => 'zh-CN',
            ];
            
            return response()->json([
                'success' => true,
                'data' => $publicSettings
            ]);
        })->name('settings.public');
    });
    
    // 认证端点
    Route::prefix('auth')->name('auth.')->group(function () {
        // 登录
        Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login'])->name('login');
        
        // 需要认证的路由
        Route::middleware('auth:sanctum')->group(function () {
            // 登出
            Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->name('logout');
            
            // 用户信息
            Route::get('user', [App\Http\Controllers\Api\AuthController::class, 'user'])->name('user');
            
            // 刷新token
            Route::post('refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh'])->name('refresh');
        });
    });
    
    // 需要认证的API路由
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        
        // 插件管理API
        Route::prefix('plugins')->name('plugins.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\PluginController::class, 'index'])->name('index');
            Route::get('{slug}', [App\Http\Controllers\Api\PluginController::class, 'show'])->name('show');
            Route::post('/', [App\Http\Controllers\Api\PluginController::class, 'store'])->name('store');
            Route::put('{slug}', [App\Http\Controllers\Api\PluginController::class, 'update'])->name('update');
            Route::delete('{slug}', [App\Http\Controllers\Api\PluginController::class, 'destroy'])->name('destroy');
            Route::post('{slug}/activate', [App\Http\Controllers\Api\PluginController::class, 'activate'])->name('activate');
            Route::post('{slug}/deactivate', [App\Http\Controllers\Api\PluginController::class, 'deactivate'])->name('deactivate');
            Route::patch('{slug}/priority', [App\Http\Controllers\Api\PluginController::class, 'updatePriority'])->name('priority');
        });
        
        // 主题管理API
        Route::prefix('themes')->name('themes.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\ThemeController::class, 'index'])->name('index');
            Route::get('{slug}', [App\Http\Controllers\Api\ThemeController::class, 'show'])->name('show');
            Route::post('/', [App\Http\Controllers\Api\ThemeController::class, 'store'])->name('store');
            Route::put('{slug}', [App\Http\Controllers\Api\ThemeController::class, 'update'])->name('update');
            Route::delete('{slug}', [App\Http\Controllers\Api\ThemeController::class, 'destroy'])->name('destroy');
            Route::post('{slug}/activate', [App\Http\Controllers\Api\ThemeController::class, 'activate'])->name('activate');
            Route::post('{slug}/deactivate', [App\Http\Controllers\Api\ThemeController::class, 'deactivate'])->name('deactivate');
        });
        
        // 用户管理API
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\UserController::class, 'index'])->name('index');
            Route::get('{user}', [App\Http\Controllers\Api\UserController::class, 'show'])->name('show');
            Route::post('/', [App\Http\Controllers\Api\UserController::class, 'store'])->name('store');
            Route::put('{user}', [App\Http\Controllers\Api\UserController::class, 'update'])->name('update');
            Route::delete('{user}', [App\Http\Controllers\Api\UserController::class, 'destroy'])->name('destroy');
            Route::post('{user}/reset-password', [App\Http\Controllers\Api\UserController::class, 'resetPassword'])->name('reset-password');
        });
        
        // 系统设置API
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\SettingController::class, 'index'])->name('index');
            Route::get('{group}', [App\Http\Controllers\Api\SettingController::class, 'group'])->name('group');
            Route::post('/', [App\Http\Controllers\Api\SettingController::class, 'store'])->name('store');
            Route::put('{key}', [App\Http\Controllers\Api\SettingController::class, 'update'])->name('update');
            Route::delete('{key}', [App\Http\Controllers\Api\SettingController::class, 'destroy'])->name('destroy');
            Route::post('bulk', [App\Http\Controllers\Api\SettingController::class, 'bulkUpdate'])->name('bulk');
        });
        
        // 文件管理API
        Route::prefix('files')->name('files.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\FileController::class, 'index'])->name('index');
            Route::get('{file}', [App\Http\Controllers\Api\FileController::class, 'show'])->name('show');
            Route::post('upload', [App\Http\Controllers\Api\FileController::class, 'upload'])->name('upload');
            Route::put('{file}', [App\Http\Controllers\Api\FileController::class, 'update'])->name('update');
            Route::delete('{file}', [App\Http\Controllers\Api\FileController::class, 'destroy'])->name('destroy');
            Route::post('bulk', [App\Http\Controllers\Api\FileController::class, 'bulkAction'])->name('bulk');
        });
        
        // 系统信息API
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('info', [App\Http\Controllers\Api\SystemController::class, 'info'])->name('info');
            Route::get('status', [App\Http\Controllers\Api\SystemController::class, 'status'])->name('status');
            Route::get('logs', [App\Http\Controllers\Api\SystemController::class, 'logs'])->name('logs');
            Route::post('cache/clear', [App\Http\Controllers\Api\SystemController::class, 'clearCache'])->name('cache.clear');
        });
        
        // 多语言API (继续使用现有的)
        Route::prefix('language')->name('language.')->group(function () {
            Route::get('supported', [App\Http\Controllers\Api\LanguageController::class, 'getSupportedLanguages'])->name('supported');
            Route::get('current', [App\Http\Controllers\Api\LanguageController::class, 'getCurrentLanguage'])->name('current');
            Route::post('set', [App\Http\Controllers\Api\LanguageController::class, 'setLanguage'])->name('set');
            Route::post('translate', [App\Http\Controllers\Api\LanguageController::class, 'translate'])->name('translate');
            Route::post('translate-batch', [App\Http\Controllers\Api\LanguageController::class, 'translateBatch'])->name('translate-batch');
            Route::get('alternate-urls', [App\Http\Controllers\Api\LanguageController::class, 'getAlternateUrls'])->name('alternate-urls');
        });
    });
    
    // 开发者工具API（仅开发环境）
    if (app()->environment(['local', 'development', 'staging'])) {
        Route::prefix('dev')->name('dev.')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            // 钩子调试
            Route::get('hooks', [App\Http\Controllers\Api\DevController::class, 'hooks'])->name('hooks');
            Route::get('hooks/{tag}', [App\Http\Controllers\Api\DevController::class, 'hookDetails'])->name('hooks.show');
            
            // 性能监控
            Route::get('performance', [App\Http\Controllers\Api\DevController::class, 'performance'])->name('performance');
            
            // 数据库信息
            Route::get('database', [App\Http\Controllers\Api\DevController::class, 'database'])->name('database');
        });
    }
});

// 未来版本预留
Route::prefix('v2')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'message' => 'API v2 is under development',
            'status' => 'coming_soon'
        ], 501);
    });
});

// Webhook端点
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('plugins/{slug}', [App\Http\Controllers\Api\WebhookController::class, 'plugin'])->name('plugin');
    Route::post('themes/{slug}', [App\Http\Controllers\Api\WebhookController::class, 'theme'])->name('theme');
    Route::post('system', [App\Http\Controllers\Api\WebhookController::class, 'system'])->name('system');
});