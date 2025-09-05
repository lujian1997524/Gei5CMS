<?php

namespace App\Services;

use App\Jobs\ProcessAsyncHook;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class AsyncHookManager
{
    protected HookManager $hookManager;
    protected array $asyncHooks = [];
    protected array $queuedHooks = [];

    public function __construct(HookManager $hookManager)
    {
        $this->hookManager = $hookManager;
    }

    public function registerAsyncHook(
        string $tag,
        callable $callback,
        int $priority = 10,
        string $pluginSlug = null,
        array $options = []
    ): bool {
        // 注册为异步钩子
        $registered = $this->hookManager->registerHook($tag, $callback, $priority, $pluginSlug, 'async');
        
        if ($registered) {
            $this->asyncHooks[$tag][] = [
                'callback' => $callback,
                'priority' => $priority,
                'plugin_slug' => $pluginSlug,
                'options' => array_merge([
                    'delay' => 0, // 延迟执行秒数
                    'queue' => 'hooks', // 队列名称
                    'max_retries' => 3, // 最大重试次数
                    'timeout' => 300, // 超时时间
                ], $options),
            ];
        }

        return $registered;
    }

    public function executeAsyncHook(string $tag, ...$args): array
    {
        $hooks = $this->getAsyncHooks($tag);
        $jobIds = [];

        foreach ($hooks as $hookData) {
            try {
                $job = new ProcessAsyncHook($tag, $args, $hookData, $hookData['options']['max_retries']);
                
                // 设置延迟
                if ($hookData['options']['delay'] > 0) {
                    $job->delay(now()->addSeconds($hookData['options']['delay']));
                }

                // 设置队列
                $job->onQueue($hookData['options']['queue']);

                // 分发任务
                $jobId = Queue::push($job);
                $jobIds[] = $jobId;

                // 记录排队的钩子
                $this->queuedHooks[$tag][] = [
                    'job_id' => $jobId,
                    'plugin_slug' => $hookData['plugin_slug'],
                    'queued_at' => now(),
                    'options' => $hookData['options'],
                ];

                Log::info("Async hook queued: {$tag}", [
                    'job_id' => $jobId,
                    'plugin_slug' => $hookData['plugin_slug'] ?? 'core',
                    'delay' => $hookData['options']['delay'],
                    'queue' => $hookData['options']['queue'],
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to queue async hook {$tag}: " . $e->getMessage());
            }
        }

        return $jobIds;
    }

    public function executeDelayedHook(string $tag, int $delaySeconds, ...$args): array
    {
        $hooks = $this->getAsyncHooks($tag);
        $jobIds = [];

        foreach ($hooks as $hookData) {
            try {
                $job = new ProcessAsyncHook($tag, $args, $hookData);
                $job->delay(now()->addSeconds($delaySeconds));
                
                $jobId = Queue::push($job);
                $jobIds[] = $jobId;

            } catch (\Exception $e) {
                Log::error("Failed to queue delayed hook {$tag}: " . $e->getMessage());
            }
        }

        return $jobIds;
    }

    public function executeBatchAsync(array $hooks): array
    {
        $allJobIds = [];

        foreach ($hooks as $hookData) {
            $tag = $hookData['tag'];
            $args = $hookData['args'] ?? [];
            
            $jobIds = $this->executeAsyncHook($tag, ...$args);
            $allJobIds[$tag] = $jobIds;
        }

        return $allJobIds;
    }

    public function scheduleRecurringHook(string $tag, string $cronExpression, ...$args): bool
    {
        try {
            // 这里可以集成Laravel的任务调度
            // 将钩子添加到调度系统中
            
            Log::info("Recurring hook scheduled: {$tag}", [
                'cron' => $cronExpression,
                'args_count' => count($args),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to schedule recurring hook {$tag}: " . $e->getMessage());
            return false;
        }
    }

    public function cancelQueuedHooks(string $tag, string $pluginSlug = null): int
    {
        $cancelled = 0;

        if (!isset($this->queuedHooks[$tag])) {
            return $cancelled;
        }

        foreach ($this->queuedHooks[$tag] as $index => $queuedHook) {
            if ($pluginSlug && $queuedHook['plugin_slug'] !== $pluginSlug) {
                continue;
            }

            try {
                // 尝试从队列中删除任务
                // 注意：这需要队列驱动支持任务删除
                Queue::forget($queuedHook['job_id']);
                
                unset($this->queuedHooks[$tag][$index]);
                $cancelled++;

                Log::info("Cancelled queued hook: {$tag}", [
                    'job_id' => $queuedHook['job_id'],
                    'plugin_slug' => $queuedHook['plugin_slug'],
                ]);

            } catch (\Exception $e) {
                Log::warning("Failed to cancel queued hook: " . $e->getMessage());
            }
        }

        return $cancelled;
    }

    public function getAsyncHookStats(): array
    {
        $stats = [
            'total_async_hooks' => 0,
            'queued_hooks' => 0,
            'hooks_by_plugin' => [],
            'hooks_by_queue' => [],
            'average_delay' => 0,
        ];

        $totalDelay = 0;
        $delayCount = 0;

        foreach ($this->asyncHooks as $tag => $hooks) {
            $stats['total_async_hooks'] += count($hooks);

            foreach ($hooks as $hookData) {
                $plugin = $hookData['plugin_slug'] ?? 'core';
                $queue = $hookData['options']['queue'];
                $delay = $hookData['options']['delay'];

                if (!isset($stats['hooks_by_plugin'][$plugin])) {
                    $stats['hooks_by_plugin'][$plugin] = 0;
                }
                $stats['hooks_by_plugin'][$plugin]++;

                if (!isset($stats['hooks_by_queue'][$queue])) {
                    $stats['hooks_by_queue'][$queue] = 0;
                }
                $stats['hooks_by_queue'][$queue]++;

                if ($delay > 0) {
                    $totalDelay += $delay;
                    $delayCount++;
                }
            }
        }

        foreach ($this->queuedHooks as $hooks) {
            $stats['queued_hooks'] += count($hooks);
        }

        if ($delayCount > 0) {
            $stats['average_delay'] = $totalDelay / $delayCount;
        }

        return $stats;
    }

    public function getQueuedHooks(string $tag = null): array
    {
        if ($tag) {
            return $this->queuedHooks[$tag] ?? [];
        }

        return $this->queuedHooks;
    }

    public function hasAsyncHooks(string $tag): bool
    {
        return !empty($this->asyncHooks[$tag]);
    }

    public function cleanupCompletedHooks(): int
    {
        $cleaned = 0;
        $cutoffTime = now()->subHours(24); // 清理24小时前的记录

        foreach ($this->queuedHooks as $tag => &$hooks) {
            foreach ($hooks as $index => $hookData) {
                if ($hookData['queued_at']->lt($cutoffTime)) {
                    unset($hooks[$index]);
                    $cleaned++;
                }
            }
            
            // 重新索引数组
            $hooks = array_values($hooks);
        }

        return $cleaned;
    }

    protected function getAsyncHooks(string $tag): array
    {
        $hooks = $this->asyncHooks[$tag] ?? [];
        
        // 按优先级排序
        usort($hooks, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $hooks;
    }
}