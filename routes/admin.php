<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FileManagerController;
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
    Route::post('settings/group/{group}', [SettingController::class, 'updateGroup'])->name('settings.update-group');
    Route::post('settings/quick-update', [SettingController::class, 'quickUpdate'])->name('settings.quick-update');
    Route::get('settings/export', [SettingController::class, 'export'])->name('settings.export');
    Route::post('settings/import', [SettingController::class, 'import'])->name('settings.import');
    
    // 管理员用户管理
    Route::resource('admin-users', AdminUserController::class);
    Route::post('admin-users/bulk', [AdminUserController::class, 'bulkAction'])->name('admin-users.bulk');
    Route::post('admin-users/{adminUser}/permissions', [AdminUserController::class, 'updatePermissions'])->name('admin-users.permissions');
    
    // 用户管理
    Route::resource('users', UserController::class);
    Route::post('users/bulk', [UserController::class, 'bulkAction'])->name('users.bulk');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{user}/toggle-verification', [UserController::class, 'toggleEmailVerification'])->name('users.toggle-verification');
    
    // 文件管理
    Route::get('file-manager', [FileManagerController::class, 'index'])->name('file-manager.index');
    Route::post('file-manager/upload', [FileManagerController::class, 'upload'])->name('file-manager.upload');
    Route::post('file-manager/create-folder', [FileManagerController::class, 'createFolder'])->name('file-manager.create-folder');
    Route::get('file-manager/files/{file}', [FileManagerController::class, 'show'])->name('file-manager.show');
    Route::get('file-manager/files/{file}/edit', [FileManagerController::class, 'edit'])->name('file-manager.edit');
    Route::put('file-manager/files/{file}', [FileManagerController::class, 'update'])->name('file-manager.update');
    Route::delete('file-manager/files/{file}', [FileManagerController::class, 'destroy'])->name('file-manager.destroy');
    Route::delete('file-manager/folders/{folder}', [FileManagerController::class, 'destroyFolder'])->name('file-manager.destroy-folder');
    Route::post('file-manager/bulk', [FileManagerController::class, 'bulkAction'])->name('file-manager.bulk');
    
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
    
    // 个人资料
    Route::get('profile', function() {
        return view('admin.profile.show');
    })->name('profile');
    
    // 系统工具
    Route::get('tools', function() {
        return view('admin.tools.index');
    })->name('tools.index');
    
    // 多语言API路由
    Route::prefix('api/language')->name('api.language.')->group(function () {
        Route::get('supported', [App\Http\Controllers\Api\LanguageController::class, 'getSupportedLanguages'])->name('supported');
        Route::get('current', [App\Http\Controllers\Api\LanguageController::class, 'getCurrentLanguage'])->name('current');
        Route::post('set', [App\Http\Controllers\Api\LanguageController::class, 'setLanguage'])->name('set');
        Route::post('translate', [App\Http\Controllers\Api\LanguageController::class, 'translate'])->name('translate');
        Route::post('translate-batch', [App\Http\Controllers\Api\LanguageController::class, 'translateBatch'])->name('translate-batch');
        Route::get('alternate-urls', [App\Http\Controllers\Api\LanguageController::class, 'getAlternateUrls'])->name('alternate-urls');
    });
});