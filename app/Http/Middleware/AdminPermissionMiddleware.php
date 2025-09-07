<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 检查是否为管理员用户
        if (!auth('admin')->check()) {
            return redirect()->route('admin.login')->with('error', '请先登录');
        }

        $user = auth('admin')->user();
        $route = $request->route();
        $routeName = $route->getName();

        // 超级管理员跳过权限检查
        if ($user->is_super_admin ?? false) {
            return $next($request);
        }

        // 获取当前路由所需权限
        $permission = $this->getRequiredPermission($routeName);
        
        if ($permission && !$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => '没有权限执行此操作',
                    'required_permission' => $permission
                ], 403);
            }
            
            abort(403, '没有权限执行此操作');
        }

        return $next($request);
    }

    /**
     * 根据路由名称获取所需权限
     */
    protected function getRequiredPermission(string $routeName): ?string
    {
        // 如果是仪表盘，所有管理员都可以访问
        if (in_array($routeName, ['admin.dashboard', 'admin.dashboard.index', 'admin.profile', 'admin.profile.update'])) {
            return null;
        }

        $permissionMap = [
            // 插件管理权限
            'admin.plugins.index' => 'plugins.view',
            'admin.plugins.show' => 'plugins.view', 
            'admin.plugins.create' => 'plugins.create',
            'admin.plugins.store' => 'plugins.create',
            'admin.plugins.edit' => 'plugins.edit',
            'admin.plugins.update' => 'plugins.edit',
            'admin.plugins.destroy' => 'plugins.delete',
            'admin.plugins.bulk' => 'plugins.bulk',
            'admin.plugins.toggle' => 'plugins.edit',
            'admin.plugins.install' => 'plugins.create',
            'admin.plugins.uninstall' => 'plugins.delete',

            // 主题管理权限
            'admin.themes.index' => 'themes.view',
            'admin.themes.show' => 'themes.view',
            'admin.themes.create' => 'themes.create', 
            'admin.themes.store' => 'themes.create',
            'admin.themes.edit' => 'themes.edit',
            'admin.themes.update' => 'themes.edit',
            'admin.themes.destroy' => 'themes.delete',
            'admin.themes.bulk' => 'themes.bulk',
            'admin.themes.activate' => 'themes.edit',
            'admin.themes.customize' => 'themes.edit',
            'admin.themes.preview' => 'themes.view',

            // 设置管理权限
            'admin.settings.index' => 'settings.view',
            'admin.settings.create' => 'settings.create',
            'admin.settings.store' => 'settings.create',
            'admin.settings.edit' => 'settings.edit',
            'admin.settings.update' => 'settings.edit',
            'admin.settings.destroy' => 'settings.delete',
            'admin.settings.bulk' => 'settings.bulk',
            'admin.settings.group' => 'settings.view',

            // 管理员用户管理权限
            'admin.admin-users.index' => 'users.view',
            'admin.admin-users.show' => 'users.view',
            'admin.admin-users.create' => 'users.create',
            'admin.admin-users.store' => 'users.create',
            'admin.admin-users.edit' => 'users.edit',
            'admin.admin-users.update' => 'users.edit',
            'admin.admin-users.destroy' => 'users.delete',
            'admin.admin-users.bulk' => 'users.bulk',
            'admin.admin-users.permissions' => 'users.permissions',

            // 前台用户管理权限  
            'admin.front-users.index' => 'front_users.view',
            'admin.front-users.show' => 'front_users.view',
            'admin.front-users.edit' => 'front_users.edit',
            'admin.front-users.update' => 'front_users.edit',
            'admin.front-users.destroy' => 'front_users.delete',
            'admin.front-users.bulk' => 'front_users.bulk',
            'admin.front-users.reset-password' => 'front_users.edit',
            'admin.front-users.toggle-verification' => 'front_users.edit',

            // 钩子管理权限
            'admin.hooks.index' => 'hooks.view',
            'admin.hooks.show' => 'hooks.view',
            'admin.hooks.create' => 'hooks.create',
            'admin.hooks.store' => 'hooks.create',
            'admin.hooks.edit' => 'hooks.edit',
            'admin.hooks.update' => 'hooks.edit',
            'admin.hooks.destroy' => 'hooks.delete',
            'admin.hooks.bulk' => 'hooks.bulk',
            'admin.hooks.toggle' => 'hooks.edit',
            'admin.hooks.category' => 'hooks.view',

            // 媒体库权限
            'admin.media.index' => 'media.view',
            'admin.media.upload' => 'media.upload',
            'admin.media.destroy' => 'media.delete',
            'admin.media.download' => 'media.view',

            // 分析权限
            'admin.analytics.index' => 'analytics.view',
            'admin.analytics.plugins' => 'analytics.view',
            'admin.analytics.themes' => 'analytics.view',
            'admin.analytics.system' => 'analytics.view',

            // 日志权限
            'admin.logs.index' => 'logs.view',
            'admin.logs.show' => 'logs.view',
            'admin.logs.destroy' => 'logs.delete',
            'admin.logs.clear' => 'logs.delete',

            // 工具权限
            'admin.tools.index' => 'tools.view',
            'admin.tools.cache.clear' => 'tools.cache',
            'admin.tools.optimize' => 'tools.optimize',
            'admin.tools.phpinfo' => 'tools.view',
            'admin.tools.database' => 'tools.view',
        ];

        return $permissionMap[$routeName] ?? null;
    }
}