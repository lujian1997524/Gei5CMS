<?php

namespace App\Providers;

use App\Services\ApiManager;
use App\Services\ApiEndpointRegistry;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApiManager::class, function ($app) {
            return new ApiManager();
        });

        $this->app->singleton(ApiEndpointRegistry::class, function ($app) {
            return new ApiEndpointRegistry($app->make(ApiManager::class));
        });

        $this->app->alias(ApiManager::class, 'api.manager');
        $this->app->alias(ApiEndpointRegistry::class, 'api.registry');
    }

    public function boot(): void
    {
        // 注册中间件
        $this->registerMiddleware();
        
        // 注册API端点
        $this->registerApiEndpoints();
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        $router->aliasMiddleware('api.auth', \App\Http\Middleware\ApiAuthMiddleware::class);
        $router->aliasMiddleware('api.rate_limit', \App\Http\Middleware\ApiRateLimitMiddleware::class);
    }

    protected function registerApiEndpoints(): void
    {
        if ($this->app->environment('production')) {
            // 在生产环境中，只注册一次
            $registry = $this->app->make(ApiEndpointRegistry::class);
            $registered = $registry->registerAllEndpoints();
            
            if ($registered > 0) {
                \Illuminate\Support\Facades\Log::info("Registered {$registered} API endpoints");
            }
        }
    }
}
