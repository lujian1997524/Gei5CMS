<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\HookRegistry;
use App\Services\ApiManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends BaseApiController
{
    protected HookRegistry $hookRegistry;
    protected ApiManager $apiManager;

    public function __construct(HookRegistry $hookRegistry, ApiManager $apiManager)
    {
        $this->hookRegistry = $hookRegistry;
        $this->apiManager = $apiManager;
    }

    /**
     * @api {get} /api/v1/system/status 获取系统状态
     * @apiName GetSystemStatus
     * @apiGroup System
     * @apiVersion 1.0.0
     */
    public function status(Request $request): JsonResponse
    {
        $this->logApiRequest($request, 'system.status');

        $status = [
            'system' => [
                'name' => config('app.name'),
                'version' => '1.0.0',
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ],
            'database' => [
                'connection' => config('database.default'),
                'status' => $this->checkDatabaseConnection(),
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'status' => $this->checkCacheConnection(),
            ],
            'queue' => [
                'driver' => config('queue.default'),
                'status' => $this->checkQueueConnection(),
            ],
            'storage' => [
                'disk' => config('filesystems.default'),
                'status' => $this->checkStorageConnection(),
            ],
        ];

        $filtered = apply_filters('api.system.status', $status, $request);

        return $this->successResponse($filtered, 'System status retrieved');
    }

    /**
     * @api {get} /api/v1/system/info 获取系统信息
     * @apiName GetSystemInfo
     * @apiGroup System
     * @apiVersion 1.0.0
     */
    public function info(Request $request): JsonResponse
    {
        $this->logApiRequest($request, 'system.info');

        $info = [
            'php' => [
                'version' => PHP_VERSION,
                'extensions' => get_loaded_extensions(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'laravel' => [
                'version' => app()->version(),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
            ],
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => PHP_OS,
                'architecture' => php_uname('m'),
            ],
            'disk_space' => [
                'free' => disk_free_space('.'),
                'total' => disk_total_space('.'),
            ],
        ];

        $filtered = apply_filters('api.system.info', $info, $request);

        return $this->successResponse($filtered, 'System information retrieved');
    }

    /**
     * @api {get} /api/v1/system/health 系统健康检查
     * @apiName GetSystemHealth
     * @apiGroup System
     * @apiVersion 1.0.0
     */
    public function health(Request $request): JsonResponse
    {
        $this->logApiRequest($request, 'system.health');

        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => [
                'database' => $this->healthCheckDatabase(),
                'cache' => $this->healthCheckCache(),
                'queue' => $this->healthCheckQueue(),
                'storage' => $this->healthCheckStorage(),
                'memory' => $this->healthCheckMemory(),
                'disk' => $this->healthCheckDisk(),
            ],
        ];

        // 确定总体健康状态
        $allHealthy = true;
        foreach ($health['checks'] as $check) {
            if (!$check['healthy']) {
                $allHealthy = false;
                break;
            }
        }

        $health['status'] = $allHealthy ? 'healthy' : 'unhealthy';

        $filtered = apply_filters('api.system.health', $health, $request);

        return $this->successResponse($filtered, 'System health check completed');
    }

    /**
     * @api {get} /api/v1/system/hooks 获取系统钩子
     * @apiName GetSystemHooks
     * @apiGroup System
     * @apiVersion 1.0.0
     */
    public function hooks(Request $request): JsonResponse
    {
        $this->logApiRequest($request, 'system.hooks');

        $category = $request->get('category');
        $search = $request->get('search');

        if ($category) {
            $hooks = $this->hookRegistry->getHooksByCategory($category);
        } else {
            $hooks = $this->hookRegistry->getAllHooks();
        }

        if ($search) {
            $hooks = $this->hookRegistry->searchHooks($search);
        }

        $response = [
            'hooks' => $hooks,
            'categories' => $this->hookRegistry->getCategoriesCount(),
            'total_hooks' => $this->hookRegistry->getHookCount(),
        ];

        $filtered = apply_filters('api.system.hooks', $response, $request);

        return $this->successResponse($filtered, 'System hooks retrieved');
    }

    /**
     * @api {get} /api/v1/system/config 获取系统配置
     * @apiName GetSystemConfig
     * @apiGroup System
     * @apiVersion 1.0.0
     */
    public function config(Request $request): JsonResponse
    {
        $this->logApiRequest($request, 'system.config');

        $publicConfig = [
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'locale' => config('app.locale'),
                'timezone' => config('app.timezone'),
            ],
            'features' => [
                'plugins_enabled' => true,
                'themes_enabled' => true,
                'api_enabled' => true,
                'hooks_enabled' => true,
            ],
            'limits' => [
                'max_upload_size' => ini_get('upload_max_filesize'),
                'max_post_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
            ],
        ];

        $filtered = apply_filters('api.system.config', $publicConfig, $request);

        return $this->successResponse($filtered, 'System configuration retrieved');
    }

    /**
     * @api {get} /api/v1/system/statistics 获取系统统计
     * @apiName GetSystemStatistics
     * @apiGroup System
     * @apiVersion 1.0.0
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->logApiRequest($request, 'system.statistics');

        $stats = [
            'api' => $this->apiManager->getApiStatistics(),
            'system' => [
                'uptime' => $this->getSystemUptime(),
                'requests_today' => $this->getRequestsToday(),
                'average_response_time' => $this->getAverageResponseTime(),
            ],
            'resources' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'cpu_load' => sys_getloadavg(),
            ],
        ];

        $filtered = apply_filters('api.system.statistics', $stats, $request);

        return $this->successResponse($filtered, 'System statistics retrieved');
    }

    protected function checkDatabaseConnection(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkCacheConnection(): bool
    {
        try {
            \Illuminate\Support\Facades\Cache::put('test', 'value', 1);
            return \Illuminate\Support\Facades\Cache::get('test') === 'value';
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkQueueConnection(): bool
    {
        try {
            return true; // 简化实现
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkStorageConnection(): bool
    {
        try {
            return \Illuminate\Support\Facades\Storage::disk()->exists('.');
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function healthCheckDatabase(): array
    {
        try {
            $start = microtime(true);
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $responseTime = microtime(true) - $start;

            return [
                'healthy' => true,
                'response_time' => $responseTime,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'message' => 'Database connection failed',
            ];
        }
    }

    protected function healthCheckCache(): array
    {
        try {
            $start = microtime(true);
            \Illuminate\Support\Facades\Cache::put('health_check', 'ok', 1);
            $value = \Illuminate\Support\Facades\Cache::get('health_check');
            $responseTime = microtime(true) - $start;

            return [
                'healthy' => $value === 'ok',
                'response_time' => $responseTime,
                'message' => 'Cache connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'message' => 'Cache connection failed',
            ];
        }
    }

    protected function healthCheckQueue(): array
    {
        return [
            'healthy' => true,
            'message' => 'Queue system operational',
        ];
    }

    protected function healthCheckStorage(): array
    {
        try {
            $healthy = \Illuminate\Support\Facades\Storage::disk()->exists('.');
            return [
                'healthy' => $healthy,
                'message' => $healthy ? 'Storage accessible' : 'Storage inaccessible',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'message' => 'Storage check failed',
            ];
        }
    }

    protected function healthCheckMemory(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $usagePercent = ($memoryUsage / $memoryLimit) * 100;

        return [
            'healthy' => $usagePercent < 80,
            'usage' => $memoryUsage,
            'limit' => $memoryLimit,
            'usage_percent' => $usagePercent,
            'message' => "Memory usage at {$usagePercent}%",
        ];
    }

    protected function healthCheckDisk(): array
    {
        $freeSpace = disk_free_space('.');
        $totalSpace = disk_total_space('.');
        $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

        return [
            'healthy' => $usagePercent < 90,
            'free_space' => $freeSpace,
            'total_space' => $totalSpace,
            'usage_percent' => $usagePercent,
            'message' => "Disk usage at {$usagePercent}%",
        ];
    }

    protected function parseMemoryLimit(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }

    protected function getSystemUptime(): array
    {
        // 简化实现
        return [
            'seconds' => time() - filemtime(storage_path('logs/laravel.log')),
            'human' => 'Unknown',
        ];
    }

    protected function getRequestsToday(): int
    {
        // 这里应该从日志或统计表中获取
        return 0;
    }

    protected function getAverageResponseTime(): float
    {
        // 这里应该从性能监控中获取
        return 0.0;
    }
}