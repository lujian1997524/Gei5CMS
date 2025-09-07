<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HookDebugController extends Controller
{
    public function index()
    {
        // 获取所有已注册的钩子
        $hooks = $this->getRegisteredHooks();
        
        // 获取钩子执行日志
        $hookLogs = $this->getHookExecutionLogs();
        
        // 获取钩子性能统计
        $performanceStats = $this->getHookPerformanceStats();
        
        return view('admin.developer.hook-debug', compact('hooks', 'hookLogs', 'performanceStats'));
    }

    /**
     * 获取已注册的钩子列表
     */
    private function getRegisteredHooks()
    {
        try {
            $hooks = DB::table('gei5_hooks')
                ->select(['hook_tag', 'callback', 'priority', 'status', 'plugin_slug', 'created_at'])
                ->orderBy('hook_tag')
                ->orderBy('priority')
                ->get()
                ->groupBy('hook_tag');

            $hookData = [];
            foreach ($hooks as $tag => $tagHooks) {
                $hookData[$tag] = [
                    'tag' => $tag,
                    'total_callbacks' => $tagHooks->count(),
                    'active_callbacks' => $tagHooks->where('status', 'active')->count(),
                    'callbacks' => $tagHooks->toArray(),
                ];
            }

            return $hookData;
        } catch (\Exception $e) {
            Log::error('Failed to get registered hooks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取钩子执行日志
     */
    private function getHookExecutionLogs($limit = 100)
    {
        $logFile = storage_path('logs/hooks.log');
        
        if (!file_exists($logFile)) {
            return [];
        }

        try {
            $logs = collect(file($logFile))
                ->reverse()
                ->take($limit)
                ->map(function ($line) {
                    // 解析日志行
                    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*Hook executed: ([^\s]+) \((\d+\.\d+)ms\)/', $line, $matches)) {
                        return [
                            'timestamp' => $matches[1],
                            'hook_tag' => $matches[2],
                            'execution_time' => floatval($matches[3]),
                            'raw_line' => trim($line),
                        ];
                    }
                    return null;
                })
                ->filter()
                ->values();

            return $logs->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to read hook logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取钩子性能统计
     */
    private function getHookPerformanceStats()
    {
        $cacheKey = 'hook_performance_stats';
        
        return Cache::remember($cacheKey, 300, function () {
            try {
                // 从日志中统计性能数据
                $logFile = storage_path('logs/hooks.log');
                
                if (!file_exists($logFile)) {
                    return [];
                }

                $stats = [];
                $lines = file($logFile);
                
                foreach ($lines as $line) {
                    if (preg_match('/Hook executed: ([^\s]+) \((\d+\.\d+)ms\)/', $line, $matches)) {
                        $hookTag = $matches[1];
                        $executionTime = floatval($matches[2]);
                        
                        if (!isset($stats[$hookTag])) {
                            $stats[$hookTag] = [
                                'count' => 0,
                                'total_time' => 0,
                                'min_time' => PHP_FLOAT_MAX,
                                'max_time' => 0,
                            ];
                        }
                        
                        $stats[$hookTag]['count']++;
                        $stats[$hookTag]['total_time'] += $executionTime;
                        $stats[$hookTag]['min_time'] = min($stats[$hookTag]['min_time'], $executionTime);
                        $stats[$hookTag]['max_time'] = max($stats[$hookTag]['max_time'], $executionTime);
                    }
                }

                // 计算平均执行时间
                foreach ($stats as $tag => &$stat) {
                    $stat['avg_time'] = $stat['total_time'] / $stat['count'];
                    $stat['tag'] = $tag;
                }

                // 按平均执行时间排序
                uasort($stats, function ($a, $b) {
                    return $b['avg_time'] <=> $a['avg_time'];
                });

                return array_values($stats);
            } catch (\Exception $e) {
                Log::error('Failed to calculate hook performance stats: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * 测试钩子执行
     */
    public function testHook(Request $request)
    {
        $request->validate([
            'hook_tag' => 'required|string',
            'test_data' => 'nullable|string',
        ]);

        $hookTag = $request->hook_tag;
        $testData = $request->test_data ? json_decode($request->test_data, true) : [];

        try {
            $startTime = microtime(true);
            
            // 执行钩子
            $result = do_action($hookTag, $testData);
            
            $executionTime = (microtime(true) - $startTime) * 1000;

            return response()->json([
                'success' => true,
                'hook_tag' => $hookTag,
                'execution_time' => round($executionTime, 2) . 'ms',
                'result' => $result,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'hook_tag' => $hookTag,
            ], 500);
        }
    }

    /**
     * 清理钩子日志
     */
    public function clearLogs()
    {
        try {
            $logFile = storage_path('logs/hooks.log');
            
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
            }

            Cache::forget('hook_performance_stats');

            return response()->json([
                'success' => true,
                'message' => '钩子日志已清理',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取钩子详细信息
     */
    public function getHookDetails(Request $request)
    {
        $hookTag = $request->get('tag');

        if (!$hookTag) {
            return response()->json(['error' => '钩子标签不能为空'], 400);
        }

        try {
            // 获取钩子的所有回调
            $callbacks = DB::table('gei5_hooks')
                ->where('hook_tag', $hookTag)
                ->orderBy('priority')
                ->get();

            // 获取最近的执行记录
            $recentExecutions = collect($this->getHookExecutionLogs(1000))
                ->where('hook_tag', $hookTag)
                ->take(20)
                ->values();

            return response()->json([
                'success' => true,
                'hook_tag' => $hookTag,
                'callbacks' => $callbacks,
                'recent_executions' => $recentExecutions,
                'total_callbacks' => $callbacks->count(),
                'active_callbacks' => $callbacks->where('status', 'active')->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}