<?php

if (!function_exists('do_action')) {
    /**
     * 执行钩子动作
     *
     * @param string $hookName 钩子名称
     * @param mixed ...$args 传递给钩子的参数
     * @return void
     */
    function do_action(string $hookName, ...$args): void
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            $hookManager->doAction($hookName, ...$args);
        }
    }
}

if (!function_exists('apply_filters')) {
    /**
     * 应用过滤器钩子
     *
     * @param string $hookName 钩子名称
     * @param mixed $value 要过滤的值
     * @param mixed ...$args 传递给钩子的额外参数
     * @return mixed 过滤后的值
     */
    function apply_filters(string $hookName, $value, ...$args)
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            return $hookManager->applyFilters($hookName, $value, ...$args);
        }
        return $value;
    }
}

if (!function_exists('add_action')) {
    /**
     * 添加动作钩子
     *
     * @param string $hookName 钩子名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级
     * @param string $source 来源标识
     * @return bool
     */
    function add_action(string $hookName, callable $callback, int $priority = 10, string $source = 'custom'): bool
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            return $hookManager->registerHook($hookName, $callback, $priority, $source);
        }
        return false;
    }
}

if (!function_exists('add_filter')) {
    /**
     * 添加过滤器钩子
     *
     * @param string $hookName 钩子名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级
     * @param string $source 来源标识
     * @return bool
     */
    function add_filter(string $hookName, callable $callback, int $priority = 10, string $source = 'custom'): bool
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            return $hookManager->registerHook($hookName, $callback, $priority, $source);
        }
        return false;
    }
}

if (!function_exists('remove_action')) {
    /**
     * 移除动作钩子
     *
     * @param string $hookName 钩子名称
     * @param string $source 来源标识
     * @return bool
     */
    function remove_action(string $hookName, string $source = 'custom'): bool
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            return $hookManager->removeHook($hookName, $source);
        }
        return false;
    }
}

if (!function_exists('remove_filter')) {
    /**
     * 移除过滤器钩子
     *
     * @param string $hookName 钩子名称
     * @param string $source 来源标识
     * @return bool
     */
    function remove_filter(string $hookName, string $source = 'custom'): bool
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            return $hookManager->removeHook($hookName, $source);
        }
        return false;
    }
}

if (!function_exists('has_action')) {
    /**
     * 检查是否存在动作钩子
     *
     * @param string $hookName 钩子名称
     * @return bool
     */
    function has_action(string $hookName): bool
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            return $hookManager->hasHook($hookName);
        }
        return false;
    }
}

if (!function_exists('has_filter')) {
    /**
     * 检查是否存在过滤器钩子
     *
     * @param string $hookName 钩子名称
     * @return bool
     */
    function has_filter(string $hookName): bool
    {
        if (app()->bound('hook.manager')) {
            $hookManager = app('hook.manager');
            return $hookManager->hasHook($hookName);
        }
        return false;
    }
}

if (!function_exists('get_plugin_path')) {
    /**
     * 获取插件路径
     *
     * @param string $pluginName 插件名称
     * @return string
     */
    function get_plugin_path(string $pluginName): string
    {
        return base_path("plugins/{$pluginName}");
    }
}

if (!function_exists('get_theme_path')) {
    /**
     * 获取主题路径
     *
     * @param string $themeName 主题名称
     * @return string
     */
    function get_theme_path(string $themeName): string
    {
        return base_path("themes/{$themeName}");
    }
}

if (!function_exists('get_plugin_url')) {
    /**
     * 获取插件URL
     *
     * @param string $pluginName 插件名称
     * @param string $path 相对路径
     * @return string
     */
    function get_plugin_url(string $pluginName, string $path = ''): string
    {
        return url("plugins/{$pluginName}/{$path}");
    }
}

if (!function_exists('get_theme_url')) {
    /**
     * 获取主题URL
     *
     * @param string $themeName 主题名称
     * @param string $path 相对路径
     * @return string
     */
    function get_theme_url(string $themeName, string $path = ''): string
    {
        return url("themes/{$themeName}/{$path}");
    }
}

if (!function_exists('get_option')) {
    /**
     * 获取系统设置值
     *
     * @param string $key 设置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    function get_option(string $key, $default = null)
    {
        if (app()->bound(\App\Models\Setting::class)) {
            return \App\Models\Setting::get($key, $default);
        }
        return $default;
    }
}

if (!function_exists('update_option')) {
    /**
     * 更新系统设置值
     *
     * @param string $key 设置键名
     * @param mixed $value 设置值
     * @param string $group 分组
     * @return bool
     */
    function update_option(string $key, $value, string $group = 'general'): bool
    {
        if (app()->bound(\App\Models\Setting::class)) {
            return \App\Models\Setting::set($key, $value, $group);
        }
        return false;
    }
}

if (!function_exists('is_plugin_active')) {
    /**
     * 检查插件是否激活
     *
     * @param string $pluginName 插件名称
     * @return bool
     */
    function is_plugin_active(string $pluginName): bool
    {
        if (app()->bound(\App\Models\Plugin::class)) {
            return \App\Models\Plugin::where('name', $pluginName)
                ->where('status', 'active')
                ->exists();
        }
        return false;
    }
}

if (!function_exists('is_theme_active')) {
    /**
     * 检查主题是否激活
     *
     * @param string $themeName 主题名称
     * @return bool
     */
    function is_theme_active(string $themeName): bool
    {
        if (app()->bound(\App\Models\Theme::class)) {
            return \App\Models\Theme::where('name', $themeName)
                ->where('status', 'active')
                ->exists();
        }
        return false;
    }
}