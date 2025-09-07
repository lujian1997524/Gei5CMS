<?php

// 检查是否在安装模式
$isInstalling = function() {
    // 检查是否已安装
    if (!file_exists(base_path('storage/installed.lock'))) {
        return true;
    }
    
    // 检查是否是安装路由
    if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/install')) {
        return true;
    }
    
    return false;
};

// 基础服务提供者（安装时也需要）
$providers = [
    App\Providers\AppServiceProvider::class,
];

// 如果不在安装模式，加载需要数据库的服务提供者
if (!$isInstalling()) {
    $providers = array_merge($providers, [
        App\Providers\ApiServiceProvider::class,
        App\Providers\CoreMenuServiceProvider::class,
        App\Providers\HookServiceProvider::class,
        App\Providers\MultiLanguageServiceProvider::class,
        App\Providers\PluginServiceProvider::class,
        App\Providers\ThemeServiceProvider::class,
    ]);
}

return $providers;
