<?php

namespace App\Jobs;

use App\Services\HookManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAsyncHook implements ShouldQueue
{
    use Queueable;

    protected string $hookTag;
    protected array $arguments;
    protected array $hookData;
    protected int $maxRetries;

    public function __construct(string $hookTag, array $arguments, array $hookData, int $maxRetries = 3)
    {
        $this->hookTag = $hookTag;
        $this->arguments = $arguments;
        $this->hookData = $hookData;
        $this->maxRetries = $maxRetries;
        
        // 设置队列属性
        $this->onQueue('hooks');
        $this->tries = $maxRetries;
        $this->timeout = 300; // 5分钟超时
    }

    public function handle(HookManager $hookManager): void
    {
        try {
            $startTime = microtime(true);
            
            Log::debug("Processing async hook: {$this->hookTag}", [
                'hook_data' => $this->hookData,
                'arguments_count' => count($this->arguments)
            ]);

            // 执行钩子回调
            $callback = $this->reconstructCallback();
            if ($callback) {
                $result = call_user_func_array($callback, $this->arguments);
                
                $executionTime = microtime(true) - $startTime;
                
                Log::info("Async hook completed: {$this->hookTag}", [
                    'execution_time' => $executionTime,
                    'plugin_slug' => $this->hookData['plugin_slug'] ?? null,
                    'result' => is_scalar($result) ? $result : gettype($result)
                ]);

                // 触发钩子完成事件
                do_action('hook.async.completed', $this->hookTag, $result, $executionTime);
            }

        } catch (\Exception $e) {
            Log::error("Async hook failed: {$this->hookTag}", [
                'error' => $e->getMessage(),
                'plugin_slug' => $this->hookData['plugin_slug'] ?? null,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);

            // 如果达到最大重试次数，触发失败事件
            if ($this->attempts() >= $this->tries) {
                do_action('hook.async.failed', $this->hookTag, $e, $this->hookData);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Async hook job failed permanently: {$this->hookTag}", [
            'error' => $exception->getMessage(),
            'plugin_slug' => $this->hookData['plugin_slug'] ?? null,
            'attempts' => $this->attempts(),
        ]);

        // 触发永久失败事件
        do_action('hook.async.permanently_failed', $this->hookTag, $exception, $this->hookData);
    }

    protected function reconstructCallback(): ?callable
    {
        try {
            $callback = $this->hookData['callback'];
            
            if (is_string($callback)) {
                return function_exists($callback) ? $callback : null;
            }
            
            if (is_array($callback) && count($callback) === 2) {
                $class = $callback[0];
                $method = $callback[1];
                
                if (is_string($class) && class_exists($class)) {
                    return [$class, $method];
                }
                
                if (is_object($class)) {
                    return [$class, $method];
                }
            }
            
            // 闭包无法在队列中序列化，需要特殊处理
            if ($callback instanceof \Closure) {
                Log::warning("Cannot process closure in async hook: {$this->hookTag}");
                return null;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Failed to reconstruct callback for async hook: " . $e->getMessage());
            return null;
        }
    }

    public function tags(): array
    {
        return ['hooks', $this->hookTag, $this->hookData['plugin_slug'] ?? 'core'];
    }
}
