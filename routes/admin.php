<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\HookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "admin" middleware group.
|
*/

// 管理员认证路由
Route::group([
    'prefix' => 'admin',
    'middleware' => ['web'],
], function () {
    // 登录页面
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');
    
    // 创建默认管理员（仅开发环境）
    Route::get('create-default-admin', [AuthController::class, 'createDefaultAdmin'])->name('admin.create-default');
});

// 管理员面板路由
Route::group([
    'prefix' => 'admin',
    'middleware' => ['web', 'auth:admin', 'admin.permission'],
    'as' => 'admin.'
], function () {
    
    // 仪表盘
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    
    // 插件管理
    Route::resource('plugins', PluginController::class);
    Route::post('plugins/{slug}/activate', [PluginController::class, 'activate'])->name('plugins.activate');
    Route::post('plugins/{slug}/deactivate', [PluginController::class, 'deactivate'])->name('plugins.deactivate');
    Route::patch('plugins/{slug}/priority', [PluginController::class, 'updatePriority'])->name('plugins.priority');
    
    // 主题管理
    Route::resource('themes', ThemeController::class);
    Route::post('themes/{slug}/activate', [ThemeController::class, 'activate'])->name('themes.activate');
    Route::post('themes/{slug}/deactivate', [ThemeController::class, 'deactivate'])->name('themes.deactivate');
    Route::get('themes/{slug}/preview', [ThemeController::class, 'preview'])->name('themes.preview');
    Route::get('themes/{slug}/customize', [ThemeController::class, 'customize'])->name('themes.customize');
    
    // 系统设置
    Route::resource('settings', SettingController::class)->except(['show']);
    Route::post('settings/bulk', [SettingController::class, 'bulkAction'])->name('settings.bulk');
    Route::get('settings/group/{group}', [SettingController::class, 'group'])->name('settings.group');
    
    // 用户管理
    Route::resource('users', UserController::class);
    Route::post('users/bulk', [UserController::class, 'bulkAction'])->name('users.bulk');
    Route::post('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions');
    
    // 钩子管理
    Route::resource('hooks', HookController::class);
    Route::post('hooks/bulk', [HookController::class, 'bulkAction'])->name('hooks.bulk');
    Route::post('hooks/{hook}/toggle', [HookController::class, 'toggle'])->name('hooks.toggle');
    Route::get('hooks/category/{category}', [HookController::class, 'category'])->name('hooks.category');
    
    // 内容管理 (由主题提供)
    Route::get('content', function () {
        $activeTheme = \App\Models\Theme::where('status', 'active')->first();
        if ($activeTheme && method_exists($activeTheme, 'getContentRoutes')) {
            return redirect()->route('admin.content.index');
        }
        return view('admin.content.empty', [
            'message' => '请先激活一个主题来管理内容'
        ]);
    })->name('content.index');
    
    // 媒体库
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::post('upload', [MediaController::class, 'upload'])->name('upload');
        Route::delete('{media}', [MediaController::class, 'destroy'])->name('destroy');
        Route::get('download/{media}', [MediaController::class, 'download'])->name('download');
    });
    
    // 数据分析
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('plugins', [AnalyticsController::class, 'plugins'])->name('plugins');
        Route::get('themes', [AnalyticsController::class, 'themes'])->name('themes');
        Route::get('system', [AnalyticsController::class, 'system'])->name('system');
    });
    
    // 系统日志
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('index');
        Route::get('{log}', [LogController::class, 'show'])->name('show');
        Route::delete('{log}', [LogController::class, 'destroy'])->name('destroy');
        Route::post('clear', [LogController::class, 'clear'])->name('clear');
    });
    
    // 个人资料
    Route::get('profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    
    // 系统工具
    Route::prefix('tools')->name('tools.')->group(function () {
        Route::get('/', [ToolsController::class, 'index'])->name('index');
        Route::post('cache/clear', [ToolsController::class, 'clearCache'])->name('cache.clear');
        Route::post('optimize', [ToolsController::class, 'optimize'])->name('optimize');
        Route::get('phpinfo', [ToolsController::class, 'phpinfo'])->name('phpinfo');
        Route::get('database', [ToolsController::class, 'database'])->name('database');
    });
});