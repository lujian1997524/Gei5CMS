<?php

namespace App\Services;

use App\Models\Hook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

class HookManager
{
    protected array $registeredHooks = [];
    protected array $hookCallbacks = [];
    protected array $executedHooks = [];

    public function registerHook(
        string $tag,
        callable $callback,
        int $priority = 10,
        string $pluginSlug = null,
        string $hookType = 'action'
    ): bool {
        try {
            // 验证钩子标签
            if (!$this->isValidHookTag($tag)) {
                throw new \InvalidArgumentException("Invalid hook tag: {$tag}");
            }

            // 生成唯一ID
            $hookId = $this->generateHookId($tag, $callback, $pluginSlug);

            // 注册到内存
            if (!isset($this->registeredHooks[$tag])) {
                $this->registeredHooks[$tag] = [];
            }

            $this->registeredHooks[$tag][$hookId] = [
                'callback' => $callback,
                'priority' => $priority,
                'plugin_slug' => $pluginSlug,
                'hook_type' => $hookType,
                'registered_at' => now(),
            ];

            // 保存到数据库（如果有插件slug）
            if ($pluginSlug) {
                // 检查是否已存在相同的钩子
                $existingHook = Hook::where([
                    'tag' => $tag,
                    'plugin_slug' => $pluginSlug,
                    'hook_type' => $hookType,
                    'priority' => $priority
                ])->first();
                
                if (!$existingHook) {
                    Hook::create([
                        'tag' => $tag,
                        'callback' => $this->serializeCallback($callback),
                        'priority' => $priority,
                        'plugin_slug' => $pluginSlug,
                        'hook_type' => $hookType,
                        'is_active' => true,
                    ]);
                    Log::debug("Hook saved to database: {$tag}");
                } else {
                    Log::debug("Hook already exists in database: {$tag}");
                }
            }

            Log::debug("Hook registered: {$tag} with priority {$priority}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to register hook {$tag}: " . $e->getMessage());
            return false;
        }
    }

    public function unregisterHook(string $tag, callable $callback = null, string $pluginSlug = null): bool
    {
        try {
            if ($callback) {
                // 移除特定回调
                $hookId = $this->generateHookId($tag, $callback, $pluginSlug);
                unset($this->registeredHooks[$tag][$hookId]);
            } else {
                // 移除所有回调（如果指定了插件）
                if ($pluginSlug) {
                    foreach ($this->registeredHooks[$tag] ?? [] as $hookId => $hookData) {
                        if ($hookData['plugin_slug'] === $pluginSlug) {
                            unset($this->registeredHooks[$tag][$hookId]);
                        }
                    }
                } else {
                    // 移除标签下的所有钩子
                    unset($this->registeredHooks[$tag]);
                }
            }

            // 从数据库删除
            $query = Hook::where('tag', $tag);
            if ($pluginSlug) {
                $query->where('plugin_slug', $pluginSlug);
            }
            $query->delete();

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to unregister hook {$tag}: " . $e->getMessage());
            return false;
        }
    }

    public function executeHook(string $tag, ...$args): array
    {
        $results = [];
        
        try {
            // 获取排序后的钩子
            $hooks = $this->getSortedHooks($tag);
            
            if (empty($hooks)) {
                return $results;
            }

            Log::debug("Executing hook: {$tag} with " . count($hooks) . " callbacks");

            foreach ($hooks as $hookId => $hookData) {
                try {
                    $startTime = microtime(true);
                    
                    // 执行钩子回调
                    $result = $this->executeCallback($hookData['callback'], $args, $hookData);
                    
                    $executionTime = microtime(true) - $startTime;
                    
                    $results[$hookId] = [
                        'result' => $result,
                        'execution_time' => $executionTime,
                        'plugin_slug' => $hookData['plugin_slug'] ?? null,
                        'priority' => $hookData['priority'],
                    ];

                    // 记录执行日志
                    $this->logHookExecution($tag, $hookId, $executionTime, $hookData);

                } catch (\Exception $e) {
                    Log::error("Hook callback failed for {$tag}: " . $e->getMessage());
                    
                    $results[$hookId] = [
                        'error' => $e->getMessage(),
                        'plugin_slug' => $hookData['plugin_slug'] ?? null,
                        'priority' => $hookData['priority'],
                    ];
                }
            }

            // 触发钩子执行事件
            Event::dispatch('hook.executed', [$tag, count($hooks), $results]);

        } catch (\Exception $e) {
            Log::error("Failed to execute hook {$tag}: " . $e->getMessage());
        }

        return $results;
    }

    public function executeHookAsync(string $tag, ...$args): void
    {
        // 将钩子执行加入队列
        Queue::push(function () use ($tag, $args) {
            $this->executeHook($tag, ...$args);
        });
    }

    public function executeFilter(string $tag, $value, ...$args)
    {
        $hooks = $this->getSortedHooks($tag);
        
        if (empty($hooks)) {
            return $value;
        }

        $filteredValue = $value;
        
        foreach ($hooks as $hookId => $hookData) {
            try {
                if ($hookData['hook_type'] === 'filter') {
                    $result = $this->executeCallback($hookData['callback'], array_merge([$filteredValue], $args), $hookData);
                    
                    // 过滤器必须返回值
                    if ($result !== null) {
                        $filteredValue = $result;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Filter callback failed for {$tag}: " . $e->getMessage());
            }
        }

        return $filteredValue;
    }

    public function doAction(string $tag, ...$args): array
    {
        return $this->executeHook($tag, ...$args);
    }

    public function applyFilters(string $tag, $value, ...$args)
    {
        return $this->executeFilter($tag, $value, ...$args);
    }

    public function removeHook(string $tag, string $source = null): bool
    {
        return $this->unregisterHook($tag, null, $source);
    }

    public function hasHook(string $tag): bool
    {
        return !empty($this->registeredHooks[$tag]);
    }

    public function getRegisteredHooks(string $tag = null): array
    {
        if ($tag) {
            return $this->registeredHooks[$tag] ?? [];
        }
        
        return $this->registeredHooks;
    }

    public function loadHooksFromDatabase(): void
    {
        $dbHooks = Hook::active()->orderBy('priority')->get();
        
        foreach ($dbHooks as $hook) {
            try {
                $callback = $this->unserializeCallback($hook->callback);
                if ($callback) {
                    $this->registerHook(
                        $hook->tag,
                        $callback,
                        $hook->priority,
                        $hook->plugin_slug,
                        $hook->hook_type
                    );
                }
            } catch (\Exception $e) {
                Log::error("Failed to load hook from database: " . $e->getMessage());
            }
        }
    }

    public function getHookStatistics(): array
    {
        $stats = [
            'total_hooks' => 0,
            'active_hooks' => 0,
            'tags' => [],
            'plugins' => [],
            'execution_stats' => [],
        ];

        foreach ($this->registeredHooks as $tag => $hooks) {
            $stats['total_hooks'] += count($hooks);
            $stats['tags'][$tag] = count($hooks);
            
            foreach ($hooks as $hookData) {
                if ($hookData['plugin_slug']) {
                    if (!isset($stats['plugins'][$hookData['plugin_slug']])) {
                        $stats['plugins'][$hookData['plugin_slug']] = 0;
                    }
                    $stats['plugins'][$hookData['plugin_slug']]++;
                }
            }
        }

        $stats['active_hooks'] = Hook::active()->count();
        return $stats;
    }

    protected function getSortedHooks(string $tag): array
    {
        if (!isset($this->registeredHooks[$tag])) {
            return [];
        }

        $hooks = $this->registeredHooks[$tag];
        
        // 按优先级排序（数字越小优先级越高）
        uasort($hooks, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $hooks;
    }

    protected function executeCallback(callable $callback, array $args, array $hookData)
    {
        // 检查是否需要异步执行
        if ($hookData['hook_type'] === 'async') {
            Queue::push(function () use ($callback, $args) {
                call_user_func_array($callback, $args);
            });
            return null;
        }

        return call_user_func_array($callback, $args);
    }

    protected function generateHookId(string $tag, callable $callback, string $pluginSlug = null): string
    {
        $callbackHash = $this->getCallbackHash($callback);
        return md5($tag . $callbackHash . ($pluginSlug ?? ''));
    }

    protected function getCallbackHash(callable $callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }
        
        if (is_array($callback)) {
            return (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]) . '::' . $callback[1];
        }
        
        if ($callback instanceof \Closure) {
            return spl_object_hash($callback);
        }
        
        return serialize($callback);
    }

    protected function serializeCallback(callable $callback): string
    {
        if (is_string($callback)) {
            return json_encode(['type' => 'function', 'value' => $callback]);
        }
        
        if (is_array($callback)) {
            return json_encode([
                'type' => 'method',
                'class' => is_object($callback[0]) ? get_class($callback[0]) : $callback[0],
                'method' => $callback[1],
            ]);
        }
        
        // 闭包无法序列化，返回占位符
        return json_encode(['type' => 'closure', 'value' => 'serialized_closure']);
    }

    protected function unserializeCallback(string $serialized): ?callable
    {
        try {
            $data = json_decode($serialized, true);
            
            switch ($data['type']) {
                case 'function':
                    return function_exists($data['value']) ? $data['value'] : null;
                
                case 'method':
                    if (class_exists($data['class']) && method_exists($data['class'], $data['method'])) {
                        return [$data['class'], $data['method']];
                    }
                    break;
                
                case 'closure':
                    // 闭包无法反序列化
                    return null;
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to unserialize callback: " . $e->getMessage());
        }
        
        return null;
    }

    protected function isValidHookTag(string $tag): bool
    {
        // 验证钩子标签格式
        return preg_match('/^[a-z_][a-z0-9_\.]*$/', $tag) === 1;
    }

    protected function logHookExecution(string $tag, string $hookId, float $executionTime, array $hookData): void
    {
        if (!isset($this->executedHooks[$tag])) {
            $this->executedHooks[$tag] = [];
        }

        $this->executedHooks[$tag][] = [
            'hook_id' => $hookId,
            'execution_time' => $executionTime,
            'plugin_slug' => $hookData['plugin_slug'] ?? null,
            'executed_at' => now(),
        ];

        // 记录慢查询
        if ($executionTime > 1.0) {
            Log::warning("Slow hook execution: {$tag} took {$executionTime}s", [
                'plugin_slug' => $hookData['plugin_slug'] ?? null,
                'priority' => $hookData['priority'],
            ]);
        }
    }
}