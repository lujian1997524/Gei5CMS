<?php

namespace App\Providers;

use App\Services\ThemeManager;
use App\Services\ThemeAutoLoader;
use App\Services\ThemeCustomizationService;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class, function ($app) {
            return new ThemeManager();
        });

        $this->app->singleton(ThemeAutoLoader::class, function ($app) {
            return new ThemeAutoLoader($app->make(ThemeManager::class));
        });

        $this->app->singleton(ThemeCustomizationService::class, function ($app) {
            return new ThemeCustomizationService($app->make(ThemeManager::class));
        });

        $this->app->alias(ThemeManager::class, 'theme.manager');
        $this->app->alias(ThemeAutoLoader::class, 'theme.loader');
        $this->app->alias(ThemeCustomizationService::class, 'theme.customizer');
    }

    public function boot(): void
    {
        $themeLoader = $this->app->make(ThemeAutoLoader::class);
        $themeLoader->boot();
    }
}
