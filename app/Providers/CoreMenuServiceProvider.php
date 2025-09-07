<?php

namespace App\Providers;

use App\Services\AdminMenuService;
use App\Facades\Hook;
use Illuminate\Support\ServiceProvider;

class CoreMenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 直接注册菜单，不依赖钩子系统
        $this->registerCoreMenus();
    }

    public function registerCoreMenus(): void
    {
        // 仪表盘菜单
        AdminMenuService::register('core.dashboard', [
            'key' => 'dashboard',
            'label' => '仪表盘',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard*',
            'position' => 'top',
            'priority' => 1,
            'icon' => 'bi bi-speedometer2'
        ]);

        // 文件管理菜单
        AdminMenuService::register('core.files', [
            'key' => 'file-manager',
            'label' => '文件管理',
            'route' => 'admin.file-manager.index',
            'active' => 'admin.file-manager.*',
            'position' => 'middle',
            'priority' => 10,
            'icon' => 'bi bi-folder'
        ]);

        // 用户管理菜单
        AdminMenuService::register('core.users', [
            'key' => 'users',
            'label' => '用户管理',
            'position' => 'middle',
            'priority' => 20,
            'icon' => 'bi bi-people',
            'children' => [
                [
                    'key' => 'admin-users',
                    'label' => '管理员',
                    'route' => 'admin.admin-users.index',
                    'active' => 'admin.admin-users.*',
                    'icon' => 'bi bi-shield-check'
                ],
                [
                    'key' => 'users',
                    'label' => '用户管理',
                    'route' => 'admin.users.index',
                    'active' => 'admin.users.*',
                    'icon' => 'bi bi-people'
                ]
            ]
        ]);

        // 系统管理菜单
        AdminMenuService::register('core.system', [
            'key' => 'system',
            'label' => '系统管理',
            'position' => 'bottom',
            'priority' => 10,
            'icon' => 'bi bi-gear',
            'children' => [
                [
                    'key' => 'themes',
                    'label' => '主题管理',
                    'route' => 'admin.themes.index',
                    'active' => 'admin.themes.*',
                    'icon' => 'bi bi-brush'
                ],
                [
                    'key' => 'plugins',
                    'label' => '插件管理',
                    'route' => 'admin.plugins.index',
                    'active' => 'admin.plugins.*',
                    'icon' => 'bi bi-puzzle-fill'
                ],
                [
                    'key' => 'settings',
                    'label' => '系统设置',
                    'route' => 'admin.settings.index',
                    'active' => 'admin.settings.*',
                    'icon' => 'bi bi-gear'
                ]
            ]
        ]);
    }
}
