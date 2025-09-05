<?php

namespace App\Providers;

use App\Services\HookManager;
use App\Services\HookDispatcher;
use App\Services\HookRegistry;
use App\Services\AsyncHookManager;
use App\Services\HookValidationService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HookServiceProvider extends ServiceProvider
{
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
        $hookManager = $this->app->make(HookManager::class);
        $hookManager->loadHooksFromDatabase();
        
        // 注册自定义 Blade 指令
        $this->registerBladeDirectives();
        
        // 注册系统级钩子
        $this->registerSystemHooks();
    }

    protected function registerSystemHooks(): void
    {
        $hookManager = $this->app->make(HookManager::class);

        // 系统启动钩子
        $hookManager->registerHook('system.boot', function () {
            do_action('system.ready');
        }, 1, 'core');

        // 错误处理钩子
        $hookManager->registerHook('system.error', function ($exception) {
            \Illuminate\Support\Facades\Log::error('System error: ' . $exception->getMessage());
        }, 1, 'core');

        // 用户认证钩子
        $hookManager->registerHook('user.login.after', function ($user) {
            \Illuminate\Support\Facades\Log::info('User logged in: ' . $user->email);
        }, 10, 'core');

        // 请求钩子
        $hookManager->registerHook('request.before', function ($request) {
            // 可以在这里添加请求日志、安全检查等
        }, 1, 'core');

        // 管理后台菜单渲染钩子
        $hookManager->registerHook('admin.sidebar.app_menu', function () {
            \App\Services\AdminMenuService::renderSidebarMenus();
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
