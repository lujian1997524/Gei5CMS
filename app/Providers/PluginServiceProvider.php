<?php

namespace App\Providers;

use App\Services\PluginManager;
use App\Services\PluginAutoLoader;
use App\Services\PluginSandbox;
use App\Services\PluginDependencyResolver;
use App\Traits\ChecksInstallStatus;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    use ChecksInstallStatus;

    public function register(): void
    {
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager();
        });

        $this->app->singleton(PluginAutoLoader::class, function ($app) {
            return new PluginAutoLoader($app->make(PluginManager::class));
        });

        $this->app->singleton(PluginSandbox::class, function ($app) {
            return new PluginSandbox();
        });

        $this->app->singleton(PluginDependencyResolver::class, function ($app) {
            return new PluginDependencyResolver();
        });

        $this->app->alias(PluginManager::class, 'plugin.manager');
        $this->app->alias(PluginAutoLoader::class, 'plugin.loader');
        $this->app->alias(PluginSandbox::class, 'plugin.sandbox');
        $this->app->alias(PluginDependencyResolver::class, 'plugin.resolver');
    }

    public function boot(): void
    {
        // 检查是否在安装过程中
        if ($this->isInstalling()) {
            return;
        }

        $pluginLoader = $this->app->make(PluginAutoLoader::class);
        $pluginLoader->boot();
    }
}
