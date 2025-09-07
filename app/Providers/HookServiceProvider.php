<?php

namespace App\Providers;

use App\Models\Hook;
use App\Services\HookManager;
use App\Services\HookDispatcher;
use App\Services\HookRegistry;
use App\Services\AsyncHookManager;
use App\Services\HookValidationService;
use App\Traits\ChecksInstallStatus;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HookServiceProvider extends ServiceProvider
{
    use ChecksInstallStatus;
    public function register(): void
    {
        $this->app->singleton(HookManager::class, function ($app) {
            return new HookManager();
        });

        $this->app->singleton(HookDispatcher::class, function ($app) {
            return new HookDispatcher($app->make(HookManager::class));
        });

        $this->app->singleton(HookRegistry::class, function ($app) {
            return new HookRegistry();
        });

        $this->app->singleton(AsyncHookManager::class, function ($app) {
            return new AsyncHookManager($app->make(HookManager::class));
        });

        $this->app->singleton(HookValidationService::class, function ($app) {
            return new HookValidationService();
        });

        $this->app->alias(HookManager::class, 'hook.manager');
        $this->app->alias(HookDispatcher::class, 'hook.dispatcher');
        $this->app->alias(HookRegistry::class, 'hook.registry');
        $this->app->alias(AsyncHookManager::class, 'hook.async');
        $this->app->alias(HookValidationService::class, 'hook.validator');
    }

    public function boot(): void
    {
        // 检查是否在安装过程中
        if ($this->isInstalling()) {
            return;
        }

        $hookManager = $this->app->make(HookManager::class);
        $hookManager->loadHooksFromDatabase();
        
        // 注册自定义 Blade 指令
        $this->registerBladeDirectives();
        
        // 注册系统级钩子
        $this->registerSystemHooks();
    }

    /**
     * 检查是否正在安装
     */
    protected function isInstalling(): bool
    {
        // 检查是否已安装
        if (!file_exists(base_path('storage/installed.lock'))) {
            return true;
        }

        // 检查是否是安装路由
        $request = $this->app['request'];
        if ($request && $request->is('install*')) {
            return true;
        }

        // 检查数据库连接是否可用
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }

    protected static $systemHooksRegistered = false;
    
    protected function registerSystemHooks(): void
    {
        // 检查数据库中是否已有系统钩子
        try {
            $existingSystemHooks = Hook::where('plugin_slug', 'core')->count();
            if ($existingSystemHooks > 0) {
                return; // 系统钩子已注册
            }
        } catch (\Exception $e) {
            // 数据库不可用或表不存在，跳过检查
        }
        
        $hookManager = $this->app->make(HookManager::class);

        // ===== 系统核心钩子 =====
        
        // 系统启动和关闭
        $hookManager->registerHook('system.boot', function () {
            do_action('system.ready');
        }, 1, 'core');
        
        $hookManager->registerHook('system.ready', function () {
            // 系统启动完成后执行
        }, 1, 'core');
        
        $hookManager->registerHook('system.shutdown', function () {
            // 系统关闭前执行
        }, 1, 'core');

        // 错误和异常处理
        $hookManager->registerHook('system.error', function ($exception) {
            \Illuminate\Support\Facades\Log::error('System error: ' . $exception->getMessage());
        }, 1, 'core');
        
        $hookManager->registerHook('system.exception', function ($exception) {
            // 异常处理钩子
        }, 1, 'core');

        // ===== 请求生命周期钩子 =====
        
        $hookManager->registerHook('request.before', function ($request) {
            // 可以在这里添加请求日志、安全检查等
        }, 1, 'core');
        
        $hookManager->registerHook('request.after', function ($request, $response) {
            // 请求完成后执行
        }, 1, 'core');
        
        $hookManager->registerHook('request.middleware.before', function ($request) {
            // 中间件执行前
        }, 1, 'core');
        
        $hookManager->registerHook('request.middleware.after', function ($request) {
            // 中间件执行后
        }, 1, 'core');

        // ===== 用户认证钩子 =====
        
        // 登录钩子
        $hookManager->registerHook('user.login.before', function ($credentials) {
            // 登录验证前
        }, 10, 'core');
        
        $hookManager->registerHook('user.login.after', function ($user) {
            \Illuminate\Support\Facades\Log::info('User logged in: ' . $user->email);
        }, 10, 'core');
        
        $hookManager->registerHook('user.login.failed', function ($credentials) {
            // 登录失败时
        }, 10, 'core');
        
        // 登出钩子
        $hookManager->registerHook('user.logout.before', function ($user) {
            // 登出前
        }, 10, 'core');
        
        $hookManager->registerHook('user.logout.after', function ($user) {
            // 登出后
        }, 10, 'core');
        
        // 用户管理钩子
        $hookManager->registerHook('user.created', function ($user) {
            // 用户创建后
        }, 10, 'core');
        
        $hookManager->registerHook('user.updated', function ($user) {
            // 用户更新后
        }, 10, 'core');
        
        $hookManager->registerHook('user.deleted', function ($user) {
            // 用户删除后
        }, 10, 'core');
        
        $hookManager->registerHook('user.password.changed', function ($user) {
            // 用户密码修改后
        }, 10, 'core');

        // ===== 管理后台钩子 =====
        
        // 菜单系统钩子
        $hookManager->registerHook('admin.menu.init', function () {
            // 菜单系统初始化
        }, 10, 'core');
        
        $hookManager->registerHook('admin.menu.filter', function ($menus) {
            // 菜单过滤器
            return $menus;
        }, 10, 'core', 'filter');
        
        // 侧边栏钩子
        $hookManager->registerHook('admin.sidebar.before', function () {
            // 侧边栏渲染前
        }, 10, 'core');
        
        $hookManager->registerHook('admin.sidebar.app_menu', function () {
            \App\Services\AdminMenuService::renderSidebarMenus();
        }, 10, 'core');
        
        $hookManager->registerHook('admin.sidebar.after', function () {
            // 侧边栏渲染后
        }, 10, 'core');
        
        $hookManager->registerHook('admin.sidebar.menu.before', function ($menu) {
            // 菜单项渲染前
        }, 10, 'core');
        
        $hookManager->registerHook('admin.sidebar.menu.after', function ($menu) {
            // 菜单项渲染后
        }, 10, 'core');
        
        // 仪表盘钩子
        $hookManager->registerHook('admin.dashboard.init', function () {
            // 仪表盘初始化
        }, 10, 'core');
        
        $hookManager->registerHook('admin.dashboard.widgets', function ($widgets) {
            // 仪表盘小部件
            return $widgets;
        }, 10, 'core', 'filter');

        // ===== 内容管理钩子 =====
        
        // 内容CRUD钩子
        $hookManager->registerHook('content.created', function ($content) {
            // 内容创建后
        }, 10, 'core');
        
        $hookManager->registerHook('content.updated', function ($content) {
            // 内容更新后
        }, 10, 'core');
        
        $hookManager->registerHook('content.deleted', function ($content) {
            // 内容删除后
        }, 10, 'core');
        
        $hookManager->registerHook('content.published', function ($content) {
            // 内容发布后
        }, 10, 'core');
        
        $hookManager->registerHook('content.unpublished', function ($content) {
            // 内容取消发布后
        }, 10, 'core');
        
        // 内容过滤器钩子
        $hookManager->registerHook('content.title', function ($title, $content) {
            // 标题过滤器
            return $title;
        }, 10, 'core', 'filter');
        
        $hookManager->registerHook('content.body', function ($body, $content) {
            // 内容过滤器
            return $body;
        }, 10, 'core', 'filter');
        
        $hookManager->registerHook('content.excerpt', function ($excerpt, $content) {
            // 摘要过滤器
            return $excerpt;
        }, 10, 'core', 'filter');

        // ===== 插件系统钩子 =====
        
        // 插件生命周期钩子
        $hookManager->registerHook('plugin.before.install', function ($pluginSlug) {
            // 插件安装前
        }, 10, 'core');
        
        $hookManager->registerHook('plugin.installed', function ($plugin) {
            // 插件安装后
        }, 10, 'core');
        
        $hookManager->registerHook('plugin.before.activate', function ($pluginSlug) {
            // 插件激活前
        }, 10, 'core');
        
        $hookManager->registerHook('plugin.activated', function ($plugin) {
            // 插件激活后
        }, 10, 'core');
        
        $hookManager->registerHook('plugin.before.deactivate', function ($pluginSlug) {
            // 插件停用前
        }, 10, 'core');
        
        $hookManager->registerHook('plugin.deactivated', function ($plugin) {
            // 插件停用后
        }, 10, 'core');
        
        $hookManager->registerHook('plugin.before.uninstall', function ($pluginSlug) {
            // 插件卸载前
        }, 10, 'core');
        
        $hookManager->registerHook('plugin.uninstalled', function ($plugin) {
            // 插件卸载后
        }, 10, 'core');
        
        // 插件配置钩子
        $hookManager->registerHook('plugin.config.updated', function ($pluginSlug, $config) {
            // 插件配置更新后
        }, 10, 'core');

        // ===== 主题系统钩子 =====
        
        // 主题生命周期钩子
        $hookManager->registerHook('theme.before.activate', function ($themeSlug) {
            // 主题激活前
        }, 10, 'core');
        
        $hookManager->registerHook('theme.activated', function ($theme) {
            // 主题激活后
        }, 10, 'core');
        
        $hookManager->registerHook('theme.before.deactivate', function ($themeSlug) {
            // 主题停用前
        }, 10, 'core');
        
        $hookManager->registerHook('theme.deactivated', function ($theme) {
            // 主题停用后
        }, 10, 'core');
        
        // 主题自定义器钩子
        $hookManager->registerHook('theme.customizer.init', function () {
            // 主题自定义器初始化
        }, 10, 'core');
        
        $hookManager->registerHook('theme.customizer.settings', function ($settings) {
            // 主题自定义器设置
            return $settings;
        }, 10, 'core', 'filter');

        // ===== 文件系统钩子 =====
        
        // 文件操作钩子
        $hookManager->registerHook('file.uploaded', function ($file) {
            // 文件上传后
        }, 10, 'core');
        
        $hookManager->registerHook('file.deleted', function ($file) {
            // 文件删除后
        }, 10, 'core');
        
        $hookManager->registerHook('file.moved', function ($oldPath, $newPath) {
            // 文件移动后
        }, 10, 'core');
        
        // 图片处理钩子
        $hookManager->registerHook('image.resized', function ($image, $dimensions) {
            // 图片调整大小后
        }, 10, 'core');
        
        $hookManager->registerHook('image.optimized', function ($image) {
            // 图片优化后
        }, 10, 'core');

        // ===== 设置系统钩子 =====
        
        // 设置更新钩子
        $hookManager->registerHook('settings.updated', function ($group, $key, $value) {
            // 设置更新后
        }, 10, 'core');
        
        $hookManager->registerHook('settings.group.updated', function ($group, $settings) {
            // 设置组更新后
        }, 10, 'core');
        
        // 设置验证钩子
        $hookManager->registerHook('settings.validate', function ($key, $value) {
            // 设置验证
            return $value;
        }, 10, 'core', 'filter');

        // ===== 缓存系统钩子 =====
        
        // 缓存操作钩子
        $hookManager->registerHook('cache.cleared', function ($cacheType) {
            // 缓存清除后
        }, 10, 'core');
        
        $hookManager->registerHook('cache.warmed', function ($cacheType) {
            // 缓存预热后
        }, 10, 'core');

        // ===== API系统钩子 =====
        
        // API请求钩子
        $hookManager->registerHook('api.request.before', function ($request) {
            // API请求前
        }, 10, 'core');
        
        $hookManager->registerHook('api.request.after', function ($request, $response) {
            // API请求后
        }, 10, 'core');
        
        $hookManager->registerHook('api.auth.failed', function ($request) {
            // API认证失败
        }, 10, 'core');

        // ===== 数据库钩子 =====
        
        // 数据库操作钩子
        $hookManager->registerHook('database.migration.before', function ($migration) {
            // 数据库迁移前
        }, 10, 'core');
        
        $hookManager->registerHook('database.migration.after', function ($migration) {
            // 数据库迁移后
        }, 10, 'core');
        
        $hookManager->registerHook('database.seed.before', function ($seeder) {
            // 数据库填充前
        }, 10, 'core');
        
        $hookManager->registerHook('database.seed.after', function ($seeder) {
            // 数据库填充后
        }, 10, 'core');

        // ===== 邮件系统钩子 =====
        
        // 邮件发送钩子
        $hookManager->registerHook('mail.sending', function ($mail) {
            // 邮件发送前
        }, 10, 'core');
        
        $hookManager->registerHook('mail.sent', function ($mail) {
            // 邮件发送后
        }, 10, 'core');
        
        $hookManager->registerHook('mail.failed', function ($mail, $exception) {
            // 邮件发送失败
        }, 10, 'core');

        // ===== 搜索系统钩子 =====
        
        // 搜索钩子
        $hookManager->registerHook('search.query', function ($query, $params) {
            // 搜索查询
            return $query;
        }, 10, 'core', 'filter');
        
        $hookManager->registerHook('search.results', function ($results, $query) {
            // 搜索结果
            return $results;
        }, 10, 'core', 'filter');

        // ===== 多语言系统钩子（已集成） =====
        
        // 多语言钩子在 MultiLanguageServiceProvider 中注册
        // 这里只注册核心的语言切换钩子
        $hookManager->registerHook('multilang.language_switched', function ($newLanguage, $oldLanguage) {
            // 语言切换后
        }, 10, 'core');
    }

    /**
     * 注册 Blade 指令
     */
    protected function registerBladeDirectives(): void
    {
        // @hook('hook_name', $arg1, $arg2) 指令 - 执行动作钩子
        Blade::directive('hook', function ($expression) {
            return "<?php app('hook.manager')->doAction({$expression}); ?>";
        });

        // @filter('filter_name', $value, $arg1, $arg2) 指令 - 执行过滤器钩子
        Blade::directive('filter', function ($expression) {
            return "<?php echo app('hook.manager')->applyFilters({$expression}); ?>";
        });

        // @hookAsync('hook_name', $arg1, $arg2) 指令 - 异步执行钩子
        Blade::directive('hookAsync', function ($expression) {
            return "<?php app('hook.manager')->executeHookAsync({$expression}); ?>";
        });

        // @hasHook('hook_name') 指令 - 检查钩子是否存在
        Blade::directive('hasHook', function ($expression) {
            return "<?php if(app('hook.manager')->hasHook({$expression})): ?>";
        });

        // @endhasHook 指令
        Blade::directive('endhasHook', function () {
            return '<?php endif; ?>';
        });

        // @hookOutput('hook_name') 指令 - 捕获钩子输出并显示
        Blade::directive('hookOutput', function ($expression) {
            return "<?php 
                ob_start();
                app('hook.manager')->doAction({$expression});
                echo ob_get_clean();
            ?>";
        });

        // @hookCount('hook_name') 指令 - 显示钩子回调数量
        Blade::directive('hookCount', function ($expression) {
            return "<?php echo count(app('hook.manager')->getRegisteredHooks({$expression})); ?>";
        });

        // @hookStats 指令 - 显示钩子统计信息
        Blade::directive('hookStats', function () {
            return "<?php 
                \$stats = app('hook.manager')->getHookStatistics();
                echo '<div class=\"hook-stats\">';
                echo '<span>总钩子数: ' . \$stats['total_hooks'] . '</span> ';
                echo '<span>活跃钩子数: ' . \$stats['active_hooks'] . '</span>';
                echo '</div>';
            ?>";
        });
    }
}
