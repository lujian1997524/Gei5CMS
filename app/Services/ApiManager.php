<?php

namespace App\Services;

use App\Models\ApiEndpoint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiManager
{
    protected array $registeredEndpoints = [];
    protected array $apiVersions = ['v1', 'v2'];
    protected string $defaultVersion = 'v1';
    protected array $middleware = ['api'];

    public function registerEndpoint(
        string $method,
        string $path,
        string $controller,
        string $action,
        array $options = []
    ): bool {
        try {
            $version = $options['version'] ?? $this->defaultVersion;
            $description = $options['description'] ?? '';
            $parameters = $options['parameters'] ?? [];
            $requiresAuth = $options['requires_auth'] ?? true;
            $permission = $options['permission'] ?? null;
            $rateLimit = $options['rate_limit'] ?? null;

            // 生成完整路径
            $fullPath = "api/{$version}/{$path}";
            
            // 注册到数据库
            $endpoint = ApiEndpoint::create([
                'endpoint_path' => $fullPath,
                'method' => strtoupper($method),
                'controller' => $controller,
                'action' => $action,
                'description' => $description,
                'parameters' => json_encode($parameters),
                'requires_auth' => $requiresAuth,
                'permission_required' => $permission,
                'is_active' => true,
                'version' => $version,
            ]);

            // 注册到内存
            $this->registeredEndpoints[$fullPath] = [
                'method' => strtoupper($method),
                'controller' => $controller,
                'action' => $action,
                'version' => $version,
                'options' => $options,
                'endpoint_id' => $endpoint->id,
            ];

            Log::info("API endpoint registered: {$method} {$fullPath}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to register API endpoint: " . $e->getMessage());
            return false;
        }
    }

    public function registerRoutes(): void
    {
        $endpoints = $this->getActiveEndpoints();

        foreach ($endpoints as $endpoint) {
            $this->createRoute($endpoint);
        }
    }

    public function getEndpoints(string $version = null, bool $activeOnly = true): array
    {
        $query = ApiEndpoint::query();

        if ($version) {
            $query->where('version', $version);
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get()->toArray();
    }

    public function getEndpointDocumentation(string $version = null): array
    {
        $endpoints = $this->getEndpoints($version);
        $documentation = [];

        foreach ($endpoints as $endpoint) {
            $path = $endpoint['endpoint_path'];
            
            if (!isset($documentation[$path])) {
                $documentation[$path] = [];
            }

            $documentation[$path][] = [
                'method' => $endpoint['method'],
                'description' => $endpoint['description'],
                'parameters' => json_decode($endpoint['parameters'], true) ?? [],
                'requires_auth' => $endpoint['requires_auth'],
                'permission_required' => $endpoint['permission_required'],
                'version' => $endpoint['version'],
            ];
        }

        return $documentation;
    }

    public function validateEndpoint(string $method, string $path, string $version): array
    {
        $fullPath = "api/{$version}/{$path}";
        
        $endpoint = ApiEndpoint::where('endpoint_path', $fullPath)
            ->where('method', strtoupper($method))
            ->where('is_active', true)
            ->first();

        if (!$endpoint) {
            return [
                'valid' => false,
                'error' => 'Endpoint not found',
            ];
        }

        return [
            'valid' => true,
            'endpoint' => $endpoint,
        ];
    }

    public function getApiStatistics(): array
    {
        $stats = [
            'total_endpoints' => ApiEndpoint::count(),
            'active_endpoints' => ApiEndpoint::where('is_active', true)->count(),
            'endpoints_by_version' => [],
            'endpoints_by_method' => [],
            'authenticated_endpoints' => ApiEndpoint::where('requires_auth', true)->count(),
            'public_endpoints' => ApiEndpoint::where('requires_auth', false)->count(),
        ];

        // 按版本统计
        foreach ($this->apiVersions as $version) {
            $stats['endpoints_by_version'][$version] = ApiEndpoint::where('version', $version)->count();
        }

        // 按方法统计
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        foreach ($methods as $method) {
            $stats['endpoints_by_method'][$method] = ApiEndpoint::where('method', $method)->count();
        }

        return $stats;
    }

    public function enableEndpoint(int $endpointId): bool
    {
        try {
            ApiEndpoint::where('id', $endpointId)->update(['is_active' => true]);
            $this->clearEndpointsCache();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to enable endpoint: " . $e->getMessage());
            return false;
        }
    }

    public function disableEndpoint(int $endpointId): bool
    {
        try {
            ApiEndpoint::where('id', $endpointId)->update(['is_active' => false]);
            $this->clearEndpointsCache();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to disable endpoint: " . $e->getMessage());
            return false;
        }
    }

    public function updateEndpoint(int $endpointId, array $data): bool
    {
        try {
            $endpoint = ApiEndpoint::find($endpointId);
            if (!$endpoint) {
                return false;
            }

            $endpoint->update($data);
            $this->clearEndpointsCache();
            
            Log::info("API endpoint updated: {$endpoint->method} {$endpoint->endpoint_path}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to update endpoint: " . $e->getMessage());
            return false;
        }
    }

    public function searchEndpoints(string $query): array
    {
        return ApiEndpoint::where(function ($q) use ($query) {
            $q->where('endpoint_path', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%")
              ->orWhere('controller', 'like', "%{$query}%");
        })->get()->toArray();
    }

    public function getEndpointsByController(string $controller): array
    {
        return ApiEndpoint::where('controller', $controller)->get()->toArray();
    }

    public function generateApiKey(string $name, array $permissions = [], int $expiresInDays = null): string
    {
        $key = 'gei5_' . bin2hex(random_bytes(32));
        
        // 这里可以将API密钥存储到数据库
        Cache::put("api_key:{$key}", [
            'name' => $name,
            'permissions' => $permissions,
            'created_at' => now(),
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ], $expiresInDays ? now()->addDays($expiresInDays) : null);

        return $key;
    }

    public function validateApiKey(string $key): array
    {
        $keyData = Cache::get("api_key:{$key}");
        
        if (!$keyData) {
            return [
                'valid' => false,
                'error' => 'Invalid API key',
            ];
        }

        if ($keyData['expires_at'] && now()->gt($keyData['expires_at'])) {
            Cache::forget("api_key:{$key}");
            return [
                'valid' => false,
                'error' => 'API key expired',
            ];
        }

        return [
            'valid' => true,
            'data' => $keyData,
        ];
    }

    protected function getActiveEndpoints(): array
    {
        $cacheKey = 'api_active_endpoints';
        
        return Cache::remember($cacheKey, 3600, function () {
            return ApiEndpoint::where('is_active', true)->get()->toArray();
        });
    }

    protected function createRoute(array $endpoint): void
    {
        $method = strtolower($endpoint['method']);
        $path = $endpoint['endpoint_path'];
        $controller = $endpoint['controller'];
        $action = $endpoint['action'];

        // 构建中间件数组
        $middleware = $this->middleware;
        
        if ($endpoint['requires_auth']) {
            $middleware[] = 'auth:api';
        }

        if ($endpoint['permission_required']) {
            $middleware[] = 'permission:' . $endpoint['permission_required'];
        }

        // 注册路由
        Route::{$method}($path, [$controller, $action])
            ->middleware($middleware)
            ->name("api.{$endpoint['version']}.{$action}");
    }

    protected function clearEndpointsCache(): void
    {
        Cache::forget('api_active_endpoints');
    }
}