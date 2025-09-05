<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ApiEndpointRegistry
{
    protected ApiManager $apiManager;
    protected array $endpoints = [];

    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
        $this->defineEndpoints();
    }

    public function registerAllEndpoints(): int
    {
        $registered = 0;

        foreach ($this->endpoints as $endpoint) {
            if ($this->apiManager->registerEndpoint(
                $endpoint['method'],
                $endpoint['path'],
                $endpoint['controller'],
                $endpoint['action'],
                $endpoint['options'] ?? []
            )) {
                $registered++;
            }
        }

        Log::info("Registered {$registered} API endpoints");
        return $registered;
    }

    public function getEndpointCount(): int
    {
        return count($this->endpoints);
    }

    public function getEndpointsByCategory(): array
    {
        $categories = [];
        
        foreach ($this->endpoints as $endpoint) {
            $category = $endpoint['category'] ?? 'uncategorized';
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }

        return $categories;
    }

    protected function defineEndpoints(): void
    {
        $this->endpoints = [
            // 系统管理 API (8个)
            ['method' => 'GET', 'path' => 'system/status', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'status', 'category' => 'system', 'options' => ['description' => '获取系统状态', 'requires_auth' => false]],
            ['method' => 'GET', 'path' => 'system/info', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'info', 'category' => 'system', 'options' => ['description' => '获取系统信息']],
            ['method' => 'GET', 'path' => 'system/health', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'health', 'category' => 'system', 'options' => ['description' => '系统健康检查', 'requires_auth' => false]],
            ['method' => 'GET', 'path' => 'system/hooks', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'hooks', 'category' => 'system', 'options' => ['description' => '获取系统钩子列表']],
            ['method' => 'GET', 'path' => 'system/config', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'config', 'category' => 'system', 'options' => ['description' => '获取系统配置']],
            ['method' => 'GET', 'path' => 'system/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'statistics', 'category' => 'system', 'options' => ['description' => '获取系统统计信息']],
            ['method' => 'POST', 'path' => 'system/cache/clear', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'clearCache', 'category' => 'system', 'options' => ['description' => '清理系统缓存', 'permission' => 'system.cache.clear']],
            ['method' => 'POST', 'path' => 'system/maintenance', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SystemController', 'action' => 'toggleMaintenance', 'category' => 'system', 'options' => ['description' => '切换维护模式', 'permission' => 'system.maintenance']],

            // 用户管理 API (15个)
            ['method' => 'GET', 'path' => 'users', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'index', 'category' => 'users', 'options' => ['description' => '获取用户列表', 'permission' => 'users.view']],
            ['method' => 'GET', 'path' => 'users/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'show', 'category' => 'users', 'options' => ['description' => '获取用户详情', 'permission' => 'users.view']],
            ['method' => 'POST', 'path' => 'users', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'store', 'category' => 'users', 'options' => ['description' => '创建用户', 'permission' => 'users.create']],
            ['method' => 'PUT', 'path' => 'users/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'update', 'category' => 'users', 'options' => ['description' => '更新用户信息', 'permission' => 'users.update']],
            ['method' => 'DELETE', 'path' => 'users/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'destroy', 'category' => 'users', 'options' => ['description' => '删除用户', 'permission' => 'users.delete']],
            ['method' => 'GET', 'path' => 'users/me', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'me', 'category' => 'users', 'options' => ['description' => '获取当前用户信息']],
            ['method' => 'PUT', 'path' => 'users/me', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'updateMe', 'category' => 'users', 'options' => ['description' => '更新当前用户信息']],
            ['method' => 'POST', 'path' => 'users/{id}/activate', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'activate', 'category' => 'users', 'options' => ['description' => '激活用户', 'permission' => 'users.activate']],
            ['method' => 'POST', 'path' => 'users/{id}/deactivate', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'deactivate', 'category' => 'users', 'options' => ['description' => '停用用户', 'permission' => 'users.deactivate']],
            ['method' => 'POST', 'path' => 'users/{id}/reset-password', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'resetPassword', 'category' => 'users', 'options' => ['description' => '重置用户密码', 'permission' => 'users.reset_password']],
            ['method' => 'GET', 'path' => 'users/{id}/permissions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'permissions', 'category' => 'users', 'options' => ['description' => '获取用户权限', 'permission' => 'users.view']],
            ['method' => 'PUT', 'path' => 'users/{id}/permissions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'updatePermissions', 'category' => 'users', 'options' => ['description' => '更新用户权限', 'permission' => 'users.permissions']],
            ['method' => 'GET', 'path' => 'users/search', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'search', 'category' => 'users', 'options' => ['description' => '搜索用户', 'permission' => 'users.view']],
            ['method' => 'POST', 'path' => 'users/bulk-actions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'bulkActions', 'category' => 'users', 'options' => ['description' => '批量操作用户', 'permission' => 'users.bulk_actions']],
            ['method' => 'GET', 'path' => 'users/{id}/activity', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\UserController', 'action' => 'activity', 'category' => 'users', 'options' => ['description' => '获取用户活动日志', 'permission' => 'users.activity']],

            // 内容管理 API (20个)
            ['method' => 'GET', 'path' => 'content', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'index', 'category' => 'content', 'options' => ['description' => '获取内容列表']],
            ['method' => 'GET', 'path' => 'content/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'show', 'category' => 'content', 'options' => ['description' => '获取内容详情']],
            ['method' => 'POST', 'path' => 'content', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'store', 'category' => 'content', 'options' => ['description' => '创建内容', 'permission' => 'content.create']],
            ['method' => 'PUT', 'path' => 'content/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'update', 'category' => 'content', 'options' => ['description' => '更新内容', 'permission' => 'content.update']],
            ['method' => 'DELETE', 'path' => 'content/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'destroy', 'category' => 'content', 'options' => ['description' => '删除内容', 'permission' => 'content.delete']],
            ['method' => 'POST', 'path' => 'content/{id}/publish', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'publish', 'category' => 'content', 'options' => ['description' => '发布内容', 'permission' => 'content.publish']],
            ['method' => 'POST', 'path' => 'content/{id}/unpublish', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'unpublish', 'category' => 'content', 'options' => ['description' => '取消发布内容', 'permission' => 'content.publish']],
            ['method' => 'GET', 'path' => 'content/search', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'search', 'category' => 'content', 'options' => ['description' => '搜索内容']],
            ['method' => 'GET', 'path' => 'content/categories', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'categories', 'category' => 'content', 'options' => ['description' => '获取内容分类']],
            ['method' => 'POST', 'path' => 'content/categories', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'storeCategory', 'category' => 'content', 'options' => ['description' => '创建内容分类', 'permission' => 'content.categories']],
            ['method' => 'GET', 'path' => 'content/tags', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'tags', 'category' => 'content', 'options' => ['description' => '获取内容标签']],
            ['method' => 'POST', 'path' => 'content/tags', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'storeTag', 'category' => 'content', 'options' => ['description' => '创建内容标签', 'permission' => 'content.tags']],
            ['method' => 'GET', 'path' => 'content/{id}/comments', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'comments', 'category' => 'content', 'options' => ['description' => '获取内容评论']],
            ['method' => 'POST', 'path' => 'content/{id}/comments', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'storeComment', 'category' => 'content', 'options' => ['description' => '创建内容评论']],
            ['method' => 'GET', 'path' => 'content/{id}/revisions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'revisions', 'category' => 'content', 'options' => ['description' => '获取内容版本历史']],
            ['method' => 'POST', 'path' => 'content/{id}/restore/{revision}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'restore', 'category' => 'content', 'options' => ['description' => '恢复内容版本', 'permission' => 'content.restore']],
            ['method' => 'POST', 'path' => 'content/bulk-actions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'bulkActions', 'category' => 'content', 'options' => ['description' => '批量操作内容', 'permission' => 'content.bulk_actions']],
            ['method' => 'GET', 'path' => 'content/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'statistics', 'category' => 'content', 'options' => ['description' => '获取内容统计信息']],
            ['method' => 'POST', 'path' => 'content/{id}/duplicate', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'duplicate', 'category' => 'content', 'options' => ['description' => '复制内容', 'permission' => 'content.create']],
            ['method' => 'GET', 'path' => 'content/templates', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ContentController', 'action' => 'templates', 'category' => 'content', 'options' => ['description' => '获取内容模板']],

            // 插件管理 API (12个)
            ['method' => 'GET', 'path' => 'plugins', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'index', 'category' => 'plugins', 'options' => ['description' => '获取插件列表', 'permission' => 'plugins.view']],
            ['method' => 'GET', 'path' => 'plugins/{slug}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'show', 'category' => 'plugins', 'options' => ['description' => '获取插件详情', 'permission' => 'plugins.view']],
            ['method' => 'POST', 'path' => 'plugins/{slug}/activate', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'activate', 'category' => 'plugins', 'options' => ['description' => '激活插件', 'permission' => 'plugins.activate']],
            ['method' => 'POST', 'path' => 'plugins/{slug}/deactivate', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'deactivate', 'category' => 'plugins', 'options' => ['description' => '停用插件', 'permission' => 'plugins.deactivate']],
            ['method' => 'POST', 'path' => 'plugins/{slug}/install', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'install', 'category' => 'plugins', 'options' => ['description' => '安装插件', 'permission' => 'plugins.install']],
            ['method' => 'POST', 'path' => 'plugins/{slug}/uninstall', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'uninstall', 'category' => 'plugins', 'options' => ['description' => '卸载插件', 'permission' => 'plugins.uninstall']],
            ['method' => 'GET', 'path' => 'plugins/{slug}/config', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'config', 'category' => 'plugins', 'options' => ['description' => '获取插件配置', 'permission' => 'plugins.config']],
            ['method' => 'PUT', 'path' => 'plugins/{slug}/config', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'updateConfig', 'category' => 'plugins', 'options' => ['description' => '更新插件配置', 'permission' => 'plugins.config']],
            ['method' => 'GET', 'path' => 'plugins/{slug}/hooks', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'hooks', 'category' => 'plugins', 'options' => ['description' => '获取插件钩子', 'permission' => 'plugins.view']],
            ['method' => 'POST', 'path' => 'plugins/{slug}/update', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'update', 'category' => 'plugins', 'options' => ['description' => '更新插件', 'permission' => 'plugins.update']],
            ['method' => 'GET', 'path' => 'plugins/available', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'available', 'category' => 'plugins', 'options' => ['description' => '获取可用插件列表', 'permission' => 'plugins.view']],
            ['method' => 'GET', 'path' => 'plugins/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PluginController', 'action' => 'statistics', 'category' => 'plugins', 'options' => ['description' => '获取插件统计信息', 'permission' => 'plugins.view']],

            // 主题管理 API (10个)
            ['method' => 'GET', 'path' => 'themes', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'index', 'category' => 'themes', 'options' => ['description' => '获取主题列表', 'permission' => 'themes.view']],
            ['method' => 'GET', 'path' => 'themes/{slug}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'show', 'category' => 'themes', 'options' => ['description' => '获取主题详情', 'permission' => 'themes.view']],
            ['method' => 'POST', 'path' => 'themes/{slug}/activate', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'activate', 'category' => 'themes', 'options' => ['description' => '激活主题', 'permission' => 'themes.activate']],
            ['method' => 'POST', 'path' => 'themes/{slug}/install', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'install', 'category' => 'themes', 'options' => ['description' => '安装主题', 'permission' => 'themes.install']],
            ['method' => 'POST', 'path' => 'themes/{slug}/uninstall', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'uninstall', 'category' => 'themes', 'options' => ['description' => '卸载主题', 'permission' => 'themes.uninstall']],
            ['method' => 'GET', 'path' => 'themes/{slug}/customizer', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'customizer', 'category' => 'themes', 'options' => ['description' => '获取主题定制器', 'permission' => 'themes.customize']],
            ['method' => 'PUT', 'path' => 'themes/{slug}/customizer', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'updateCustomizer', 'category' => 'themes', 'options' => ['description' => '更新主题定制', 'permission' => 'themes.customize']],
            ['method' => 'GET', 'path' => 'themes/active', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'active', 'category' => 'themes', 'options' => ['description' => '获取当前激活主题']],
            ['method' => 'GET', 'path' => 'themes/available', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'available', 'category' => 'themes', 'options' => ['description' => '获取可用主题列表', 'permission' => 'themes.view']],
            ['method' => 'POST', 'path' => 'themes/{slug}/update', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ThemeController', 'action' => 'update', 'category' => 'themes', 'options' => ['description' => '更新主题', 'permission' => 'themes.update']],

            // 媒体管理 API (12个)
            ['method' => 'GET', 'path' => 'media', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'index', 'category' => 'media', 'options' => ['description' => '获取媒体列表']],
            ['method' => 'GET', 'path' => 'media/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'show', 'category' => 'media', 'options' => ['description' => '获取媒体详情']],
            ['method' => 'POST', 'path' => 'media/upload', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'upload', 'category' => 'media', 'options' => ['description' => '上传媒体文件', 'permission' => 'media.upload']],
            ['method' => 'DELETE', 'path' => 'media/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'destroy', 'category' => 'media', 'options' => ['description' => '删除媒体文件', 'permission' => 'media.delete']],
            ['method' => 'PUT', 'path' => 'media/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'update', 'category' => 'media', 'options' => ['description' => '更新媒体信息', 'permission' => 'media.update']],
            ['method' => 'POST', 'path' => 'media/{id}/resize', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'resize', 'category' => 'media', 'options' => ['description' => '调整媒体尺寸', 'permission' => 'media.edit']],
            ['method' => 'POST', 'path' => 'media/{id}/crop', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'crop', 'category' => 'media', 'options' => ['description' => '裁剪媒体文件', 'permission' => 'media.edit']],
            ['method' => 'GET', 'path' => 'media/folders', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'folders', 'category' => 'media', 'options' => ['description' => '获取媒体文件夹']],
            ['method' => 'POST', 'path' => 'media/folders', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'createFolder', 'category' => 'media', 'options' => ['description' => '创建媒体文件夹', 'permission' => 'media.folders']],
            ['method' => 'POST', 'path' => 'media/bulk-upload', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'bulkUpload', 'category' => 'media', 'options' => ['description' => '批量上传媒体文件', 'permission' => 'media.upload']],
            ['method' => 'GET', 'path' => 'media/search', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'search', 'category' => 'media', 'options' => ['description' => '搜索媒体文件']],
            ['method' => 'GET', 'path' => 'media/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\MediaController', 'action' => 'statistics', 'category' => 'media', 'options' => ['description' => '获取媒体统计信息']],

            // API管理 API (8个)
            ['method' => 'GET', 'path' => 'api-keys', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiKeyController', 'action' => 'index', 'category' => 'api', 'options' => ['description' => '获取API密钥列表', 'permission' => 'api.keys.view']],
            ['method' => 'POST', 'path' => 'api-keys', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiKeyController', 'action' => 'store', 'category' => 'api', 'options' => ['description' => '创建API密钥', 'permission' => 'api.keys.create']],
            ['method' => 'DELETE', 'path' => 'api-keys/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiKeyController', 'action' => 'destroy', 'category' => 'api', 'options' => ['description' => '删除API密钥', 'permission' => 'api.keys.delete']],
            ['method' => 'GET', 'path' => 'api/endpoints', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiController', 'action' => 'endpoints', 'category' => 'api', 'options' => ['description' => '获取API端点列表']],
            ['method' => 'GET', 'path' => 'api/documentation', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiController', 'action' => 'documentation', 'category' => 'api', 'options' => ['description' => '获取API文档', 'requires_auth' => false]],
            ['method' => 'GET', 'path' => 'api/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiController', 'action' => 'statistics', 'category' => 'api', 'options' => ['description' => '获取API统计信息', 'permission' => 'api.statistics']],
            ['method' => 'GET', 'path' => 'api/rate-limits', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiController', 'action' => 'rateLimits', 'category' => 'api', 'options' => ['description' => '获取API限制信息']],
            ['method' => 'POST', 'path' => 'api/test', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\ApiController', 'action' => 'test', 'category' => 'api', 'options' => ['description' => 'API连接测试', 'requires_auth' => false]],

            // 设置管理 API (8个)
            ['method' => 'GET', 'path' => 'settings', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'index', 'category' => 'settings', 'options' => ['description' => '获取系统设置', 'permission' => 'settings.view']],
            ['method' => 'GET', 'path' => 'settings/{group}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'group', 'category' => 'settings', 'options' => ['description' => '获取设置组', 'permission' => 'settings.view']],
            ['method' => 'PUT', 'path' => 'settings', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'update', 'category' => 'settings', 'options' => ['description' => '更新系统设置', 'permission' => 'settings.update']],
            ['method' => 'PUT', 'path' => 'settings/{group}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'updateGroup', 'category' => 'settings', 'options' => ['description' => '更新设置组', 'permission' => 'settings.update']],
            ['method' => 'POST', 'path' => 'settings/reset/{group}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'resetGroup', 'category' => 'settings', 'options' => ['description' => '重置设置组', 'permission' => 'settings.reset']],
            ['method' => 'POST', 'path' => 'settings/import', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'import', 'category' => 'settings', 'options' => ['description' => '导入设置', 'permission' => 'settings.import']],
            ['method' => 'GET', 'path' => 'settings/export', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'export', 'category' => 'settings', 'options' => ['description' => '导出设置', 'permission' => 'settings.export']],
            ['method' => 'GET', 'path' => 'settings/schema', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SettingController', 'action' => 'schema', 'category' => 'settings', 'options' => ['description' => '获取设置结构', 'permission' => 'settings.view']],

            // 权限管理 API (10个)
            ['method' => 'GET', 'path' => 'permissions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PermissionController', 'action' => 'index', 'category' => 'permissions', 'options' => ['description' => '获取权限列表', 'permission' => 'permissions.view']],
            ['method' => 'GET', 'path' => 'permissions/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PermissionController', 'action' => 'show', 'category' => 'permissions', 'options' => ['description' => '获取权限详情', 'permission' => 'permissions.view']],
            ['method' => 'POST', 'path' => 'permissions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PermissionController', 'action' => 'store', 'category' => 'permissions', 'options' => ['description' => '创建权限', 'permission' => 'permissions.create']],
            ['method' => 'PUT', 'path' => 'permissions/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PermissionController', 'action' => 'update', 'category' => 'permissions', 'options' => ['description' => '更新权限', 'permission' => 'permissions.update']],
            ['method' => 'DELETE', 'path' => 'permissions/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PermissionController', 'action' => 'destroy', 'category' => 'permissions', 'options' => ['description' => '删除权限', 'permission' => 'permissions.delete']],
            ['method' => 'GET', 'path' => 'permissions/groups', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\PermissionController', 'action' => 'groups', 'category' => 'permissions', 'options' => ['description' => '获取权限组', 'permission' => 'permissions.view']],
            ['method' => 'GET', 'path' => 'roles', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\RoleController', 'action' => 'index', 'category' => 'permissions', 'options' => ['description' => '获取角色列表', 'permission' => 'roles.view']],
            ['method' => 'POST', 'path' => 'roles', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\RoleController', 'action' => 'store', 'category' => 'permissions', 'options' => ['description' => '创建角色', 'permission' => 'roles.create']],
            ['method' => 'PUT', 'path' => 'roles/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\RoleController', 'action' => 'update', 'category' => 'permissions', 'options' => ['description' => '更新角色', 'permission' => 'roles.update']],
            ['method' => 'DELETE', 'path' => 'roles/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\RoleController', 'action' => 'destroy', 'category' => 'permissions', 'options' => ['description' => '删除角色', 'permission' => 'roles.delete']],

            // 日志和监控 API (7个)
            ['method' => 'GET', 'path' => 'logs', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\LogController', 'action' => 'index', 'category' => 'logs', 'options' => ['description' => '获取系统日志', 'permission' => 'logs.view']],
            ['method' => 'GET', 'path' => 'logs/{type}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\LogController', 'action' => 'byType', 'category' => 'logs', 'options' => ['description' => '获取指定类型日志', 'permission' => 'logs.view']],
            ['method' => 'DELETE', 'path' => 'logs', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\LogController', 'action' => 'clear', 'category' => 'logs', 'options' => ['description' => '清理日志', 'permission' => 'logs.clear']],
            ['method' => 'GET', 'path' => 'analytics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\AnalyticsController', 'action' => 'index', 'category' => 'logs', 'options' => ['description' => '获取分析数据', 'permission' => 'analytics.view']],
            ['method' => 'GET', 'path' => 'analytics/visitors', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\AnalyticsController', 'action' => 'visitors', 'category' => 'logs', 'options' => ['description' => '获取访客统计', 'permission' => 'analytics.view']],
            ['method' => 'GET', 'path' => 'analytics/performance', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\AnalyticsController', 'action' => 'performance', 'category' => 'logs', 'options' => ['description' => '获取性能数据', 'permission' => 'analytics.view']],
            ['method' => 'POST', 'path' => 'analytics/track', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\AnalyticsController', 'action' => 'track', 'category' => 'logs', 'options' => ['description' => '追踪事件', 'requires_auth' => false]],

            // 搜索 API (5个)
            ['method' => 'GET', 'path' => 'search', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SearchController', 'action' => 'search', 'category' => 'search', 'options' => ['description' => '全局搜索']],
            ['method' => 'POST', 'path' => 'search/index', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SearchController', 'action' => 'rebuildIndex', 'category' => 'search', 'options' => ['description' => '重建搜索索引', 'permission' => 'search.rebuild']],
            ['method' => 'GET', 'path' => 'search/suggestions', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SearchController', 'action' => 'suggestions', 'category' => 'search', 'options' => ['description' => '获取搜索建议']],
            ['method' => 'GET', 'path' => 'search/trending', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SearchController', 'action' => 'trending', 'category' => 'search', 'options' => ['description' => '获取热门搜索']],
            ['method' => 'GET', 'path' => 'search/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\SearchController', 'action' => 'statistics', 'category' => 'search', 'options' => ['description' => '获取搜索统计', 'permission' => 'search.statistics']],

            // 缓存管理 API (6个)
            ['method' => 'GET', 'path' => 'cache/status', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\CacheController', 'action' => 'status', 'category' => 'cache', 'options' => ['description' => '获取缓存状态', 'permission' => 'cache.view']],
            ['method' => 'POST', 'path' => 'cache/clear', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\CacheController', 'action' => 'clear', 'category' => 'cache', 'options' => ['description' => '清理所有缓存', 'permission' => 'cache.clear']],
            ['method' => 'POST', 'path' => 'cache/clear/{type}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\CacheController', 'action' => 'clearType', 'category' => 'cache', 'options' => ['description' => '清理指定类型缓存', 'permission' => 'cache.clear']],
            ['method' => 'POST', 'path' => 'cache/warm', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\CacheController', 'action' => 'warm', 'category' => 'cache', 'options' => ['description' => '预热缓存', 'permission' => 'cache.warm']],
            ['method' => 'GET', 'path' => 'cache/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\CacheController', 'action' => 'statistics', 'category' => 'cache', 'options' => ['description' => '获取缓存统计', 'permission' => 'cache.view']],
            ['method' => 'GET', 'path' => 'cache/keys', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\CacheController', 'action' => 'keys', 'category' => 'cache', 'options' => ['description' => '获取缓存键列表', 'permission' => 'cache.view']],

            // 队列管理 API (6个)
            ['method' => 'GET', 'path' => 'queues', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\QueueController', 'action' => 'status', 'category' => 'queues', 'options' => ['description' => '获取队列状态', 'permission' => 'queues.view']],
            ['method' => 'GET', 'path' => 'queues/{queue}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\QueueController', 'action' => 'show', 'category' => 'queues', 'options' => ['description' => '获取队列详情', 'permission' => 'queues.view']],
            ['method' => 'POST', 'path' => 'queues/{queue}/clear', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\QueueController', 'action' => 'clear', 'category' => 'queues', 'options' => ['description' => '清空队列', 'permission' => 'queues.clear']],
            ['method' => 'POST', 'path' => 'queues/jobs/{job}/retry', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\QueueController', 'action' => 'retry', 'category' => 'queues', 'options' => ['description' => '重试任务', 'permission' => 'queues.retry']],
            ['method' => 'GET', 'path' => 'queues/failed', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\QueueController', 'action' => 'failed', 'category' => 'queues', 'options' => ['description' => '获取失败任务', 'permission' => 'queues.view']],
            ['method' => 'GET', 'path' => 'queues/statistics', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\QueueController', 'action' => 'statistics', 'category' => 'queues', 'options' => ['description' => '获取队列统计', 'permission' => 'queues.view']],

            // 备份管理 API (5个)
            ['method' => 'GET', 'path' => 'backups', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\BackupController', 'action' => 'index', 'category' => 'backups', 'options' => ['description' => '获取备份列表', 'permission' => 'backups.view']],
            ['method' => 'POST', 'path' => 'backups', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\BackupController', 'action' => 'create', 'category' => 'backups', 'options' => ['description' => '创建备份', 'permission' => 'backups.create']],
            ['method' => 'POST', 'path' => 'backups/{id}/restore', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\BackupController', 'action' => 'restore', 'category' => 'backups', 'options' => ['description' => '恢复备份', 'permission' => 'backups.restore']],
            ['method' => 'DELETE', 'path' => 'backups/{id}', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\BackupController', 'action' => 'destroy', 'category' => 'backups', 'options' => ['description' => '删除备份', 'permission' => 'backups.delete']],
            ['method' => 'GET', 'path' => 'backups/{id}/download', 'controller' => 'App\\Http\\Controllers\\Api\\V1\\BackupController', 'action' => 'download', 'category' => 'backups', 'options' => ['description' => '下载备份', 'permission' => 'backups.download']],
        ];
    }
}