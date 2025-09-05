<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;

class HookDispatcher
{
    protected HookManager $hookManager;
    protected array $dispatchingStack = [];
    protected array $benchmarkData = [];

    public function __construct(HookManager $hookManager)
    {
        $this->hookManager = $hookManager;
    }

    public function dispatch(string $tag, ...$args): array
    {
        // 防止无限递归调用
        if ($this->isCircularDispatch($tag)) {
            Log::warning("Circular hook dispatch detected for tag: {$tag}");
            return [];
        }

        $this->dispatchingStack[] = $tag;
        
        try {
            $startTime = microtime(true);
            $results = $this->hookManager->executeHook($tag, ...$args);
            $totalTime = microtime(true) - $startTime;

            $this->recordBenchmark($tag, $totalTime, count($results));
            
            return $results;
            
        } finally {
            array_pop($this->dispatchingStack);
        }
    }

    public function dispatchAsync(string $tag, ...$args): void
    {
        Queue::push(function () use ($tag, $args) {
            $this->dispatch($tag, ...$args);
        });
    }

    public function filter(string $tag, $value, ...$args)
    {
        if ($this->isCircularDispatch($tag)) {
            Log::warning("Circular filter dispatch detected for tag: {$tag}");
            return $value;
        }

        $this->dispatchingStack[] = $tag;
        
        try {
            return $this->hookManager->executeFilter($tag, $value, ...$args);
        } finally {
            array_pop($this->dispatchingStack);
        }
    }

    public function dispatchBatch(array $hooks): array
    {
        $results = [];
        
        foreach ($hooks as $hookData) {
            $tag = $hookData['tag'];
            $args = $hookData['args'] ?? [];
            
            try {
                $results[$tag] = $this->dispatch($tag, ...$args);
            } catch (\Exception $e) {
                Log::error("Batch hook dispatch failed for {$tag}: " . $e->getMessage());
                $results[$tag] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    public function dispatchConditional(string $tag, callable $condition, ...$args): array
    {
        if (call_user_func($condition, ...$args)) {
            return $this->dispatch($tag, ...$args);
        }
        
        return [];
    }

    public function dispatchWithTimeout(string $tag, int $timeoutSeconds, ...$args): array
    {
        $startTime = time();
        $results = [];
        
        // 获取钩子列表
        $hooks = $this->hookManager->getRegisteredHooks($tag);
        
        foreach ($hooks as $hookId => $hookData) {
            if (time() - $startTime >= $timeoutSeconds) {
                Log::warning("Hook dispatch timeout reached for tag: {$tag}");
                break;
            }
            
            try {
                $hookResult = $this->executeWithTimeout($hookData['callback'], $args, $timeoutSeconds - (time() - $startTime));
                $results[$hookId] = $hookResult;
            } catch (\Exception $e) {
                $results[$hookId] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    public function dispatchPriority(string $tag, int $minPriority, int $maxPriority, ...$args): array
    {
        $hooks = $this->hookManager->getRegisteredHooks($tag);
        $filteredHooks = [];
        
        foreach ($hooks as $hookId => $hookData) {
            $priority = $hookData['priority'];
            if ($priority >= $minPriority && $priority <= $maxPriority) {
                $filteredHooks[$hookId] = $hookData;
            }
        }
        
        if (empty($filteredHooks)) {
            return [];
        }
        
        // 临时替换钩子列表
        $originalHooks = $this->hookManager->getRegisteredHooks($tag);
        $this->hookManager->registeredHooks[$tag] = $filteredHooks;
        
        try {
            return $this->dispatch($tag, ...$args);
        } finally {
            $this->hookManager->registeredHooks[$tag] = $originalHooks;
        }
    }

    public function dispatchPlugin(string $pluginSlug, string $tag, ...$args): array
    {
        $hooks = $this->hookManager->getRegisteredHooks($tag);
        $results = [];
        
        foreach ($hooks as $hookId => $hookData) {
            if ($hookData['plugin_slug'] === $pluginSlug) {
                try {
                    $result = call_user_func_array($hookData['callback'], $args);
                    $results[$hookId] = [
                        'result' => $result,
                        'plugin_slug' => $pluginSlug,
                        'priority' => $hookData['priority'],
                    ];
                } catch (\Exception $e) {
                    $results[$hookId] = [
                        'error' => $e->getMessage(),
                        'plugin_slug' => $pluginSlug,
                        'priority' => $hookData['priority'],
                    ];
                }
            }
        }
        
        return $results;
    }

    public function getBenchmarkData(): array
    {
        return $this->benchmarkData;
    }

    public function getSlowHooks(float $threshold = 0.5): array
    {
        $slowHooks = [];
        
        foreach ($this->benchmarkData as $tag => $data) {
            if ($data['avg_execution_time'] > $threshold) {
                $slowHooks[$tag] = $data;
            }
        }
        
        // 按执行时间排序
        uasort($slowHooks, function ($a, $b) {
            return $b['avg_execution_time'] <=> $a['avg_execution_time'];
        });
        
        return $slowHooks;
    }

    public function getDispatchStatistics(): array
    {
        $stats = [
            'total_dispatches' => array_sum(array_column($this->benchmarkData, 'dispatch_count')),
            'total_execution_time' => array_sum(array_column($this->benchmarkData, 'total_time')),
            'average_hooks_per_dispatch' => 0,
            'most_used_hooks' => [],
            'slowest_hooks' => $this->getSlowHooks(),
        ];
        
        // 计算平均钩子数
        if ($stats['total_dispatches'] > 0) {
            $totalHooks = array_sum(array_column($this->benchmarkData, 'total_hooks'));
            $stats['average_hooks_per_dispatch'] = $totalHooks / $stats['total_dispatches'];
        }
        
        // 最常用的钩子
        $usage = [];
        foreach ($this->benchmarkData as $tag => $data) {
            $usage[$tag] = $data['dispatch_count'];
        }
        arsort($usage);
        $stats['most_used_hooks'] = array_slice($usage, 0, 10, true);
        
        return $stats;
    }

    public function clearBenchmarkData(): void
    {
        $this->benchmarkData = [];
    }

    protected function isCircularDispatch(string $tag): bool
    {
        return in_array($tag, $this->dispatchingStack);
    }

    protected function executeWithTimeout(callable $callback, array $args, int $timeoutSeconds)
    {
        $startTime = time();
        
        // 简单的超时检查（这里可以用更复杂的方式实现）
        $result = call_user_func_array($callback, $args);
        
        if (time() - $startTime >= $timeoutSeconds) {
            throw new \RuntimeException("Hook execution timeout");
        }
        
        return $result;
    }

    protected function recordBenchmark(string $tag, float $executionTime, int $hookCount): void
    {
        if (!isset($this->benchmarkData[$tag])) {
            $this->benchmarkData[$tag] = [
                'dispatch_count' => 0,
                'total_time' => 0,
                'total_hooks' => 0,
                'avg_execution_time' => 0,
                'min_execution_time' => PHP_FLOAT_MAX,
                'max_execution_time' => 0,
            ];
        }
        
        $data = &$this->benchmarkData[$tag];
        $data['dispatch_count']++;
        $data['total_time'] += $executionTime;
        $data['total_hooks'] += $hookCount;
        $data['avg_execution_time'] = $data['total_time'] / $data['dispatch_count'];
        $data['min_execution_time'] = min($data['min_execution_time'], $executionTime);
        $data['max_execution_time'] = max($data['max_execution_time'], $executionTime);
    }
}

// 全局钩子调度函数
if (!function_exists('do_action')) {
    function do_action(string $tag, ...$args): array
    {
        return app(HookDispatcher::class)->dispatch($tag, ...$args);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $tag, $value, ...$args)
    {
        return app(HookDispatcher::class)->filter($tag, $value, ...$args);
    }
}

if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, string $pluginSlug = null): bool
    {
        return app(HookManager::class)->registerHook($tag, $callback, $priority, $pluginSlug, 'action');
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, string $pluginSlug = null): bool
    {
        return app(HookManager::class)->registerHook($tag, $callback, $priority, $pluginSlug, 'filter');
    }
}

if (!function_exists('remove_action')) {
    function remove_action(string $tag, callable $callback = null, string $pluginSlug = null): bool
    {
        return app(HookManager::class)->unregisterHook($tag, $callback, $pluginSlug);
    }
}

if (!function_exists('has_action')) {
    function has_action(string $tag): bool
    {
        return app(HookManager::class)->hasHook($tag);
    }
}