<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Models\Theme;
use App\Models\Setting;
use App\Models\Hook;
use App\Models\AdminUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        // 中间件已经在路由中定义，这里不需要重复定义
    }

    public function index(Request $request)
    {
        do_action('admin.dashboard.loading');

        $stats = $this->getSystemStats();
        $widgets = $this->getWidgets();
        $recentActivity = $this->getRecentActivity();
        $quickActions = $this->getQuickActions();

        do_action('admin.dashboard.loaded', $stats, $widgets);

        return view('admin.dashboard', compact(
            'stats',
            'widgets', 
            'recentActivity',
            'quickActions'
        ));
    }

    protected function getSystemStats(): array
    {
        // 站长关心的是网站运营数据，由当前激活的主题提供
        // 如果没有激活主题，显示基础的系统信息
        $activeTheme = Theme::where('status', 'active')->first();
        
        if ($activeTheme) {
            // 让主题提供业务统计数据
            $stats = apply_filters('theme.dashboard.stats', [
                'primary' => [
                    'label' => '主要数据',
                    'value' => 0,
                    'description' => '等待主题提供',
                    'trend' => '0%',
                    'trend_type' => 'neutral',
                ],
                'secondary' => [
                    'label' => '次要数据', 
                    'value' => 0,
                    'description' => '等待主题提供',
                    'trend' => '0%',
                    'trend_type' => 'neutral',
                ],
                'activity' => [
                    'label' => '活动数据',
                    'value' => 0,
                    'description' => '等待主题提供',
                    'trend' => '0%',
                    'trend_type' => 'neutral',
                ],
                'overview' => [
                    'label' => '总览数据',
                    'value' => 0,
                    'description' => '等待主题提供',
                    'trend' => '0%',
                    'trend_type' => 'neutral',
                ]
            ]);
        } else {
            // 没有激活主题时的默认统计，包含用户和管理员数据
            $stats = [
                'users' => [
                    'label' => '网站用户',
                    'value' => User::count(),
                    'description' => '已注册用户总数',
                    'trend' => $this->getUserTrend(),
                    'trend_type' => 'success',
                ],
                'admins' => [
                    'label' => '管理员',
                    'value' => AdminUser::where('status', 'active')->count(),
                    'description' => '活跃管理员账户',
                    'trend' => AdminUser::count() . ' 总数',
                    'trend_type' => 'info',
                ],
                'themes' => [
                    'label' => '可用主题',
                    'value' => Theme::count(),
                    'description' => '选择一个主题开始使用',
                    'trend' => '稳定',
                    'trend_type' => 'success',
                ],
                'plugins' => [
                    'label' => '可用插件',
                    'value' => Plugin::count(),
                    'description' => '扩展网站功能',
                    'trend' => '可扩展',
                    'trend_type' => 'info',
                ],
            ];
        }

        return apply_filters('admin.dashboard.stats', $stats);
    }

    protected function getWidgets(): array
    {
        $activeTheme = Theme::where('status', 'active')->first();
        
        if ($activeTheme) {
            // 有激活主题时，让主题决定显示什么系统状态
            $widgets = apply_filters('theme.dashboard.widgets', [
                'site_status' => [
                    'title' => '网站状态',
                    'icon' => 'bi bi-globe',
                    'color' => 'success',
                    'value' => '运行正常',
                    'description' => '网站访问正常',
                ],
                'theme_info' => [
                    'title' => '当前应用',
                    'icon' => 'bi bi-window',
                    'color' => 'info', 
                    'value' => $activeTheme->name,
                    'description' => $activeTheme->description ?? '正在运行的应用',
                ],
                'performance' => [
                    'title' => '运行状态',
                    'icon' => 'bi bi-activity',
                    'color' => 'success',
                    'value' => '优秀',
                    'description' => '网站运行流畅',
                ],
                'storage' => [
                    'title' => '存储使用',
                    'icon' => 'bi bi-hdd',
                    'color' => 'warning',
                    'value' => $this->getStorageUsage(),
                    'description' => '磁盘空间使用情况',
                ],
            ]);
        } else {
            // 没有激活主题时，引导用户选择主题
            $widgets = [
                'welcome' => [
                    'title' => '欢迎使用',
                    'icon' => 'bi bi-rocket',
                    'color' => 'primary',
                    'value' => 'Gei5CMS',
                    'description' => '请先选择一个主题开始使用',
                ],
                'themes_available' => [
                    'title' => '可选主题',
                    'icon' => 'bi bi-palette',
                    'color' => 'info', 
                    'value' => Theme::count() . ' 个',
                    'description' => '博客、商城、论坛等应用',
                ],
                'plugins_available' => [
                    'title' => '可用插件',
                    'icon' => 'bi bi-puzzle-fill',
                    'color' => 'success',
                    'value' => Plugin::count() . ' 个',
                    'description' => '支付、短信、邮件等功能',
                ],
                'system_ready' => [
                    'title' => '系统状态',
                    'icon' => 'bi bi-check',
                    'color' => 'success',
                    'value' => '就绪',
                    'description' => '框架已准备就绪',
                ],
            ];
        }

        return apply_filters('admin.dashboard.widgets', $widgets);
    }

    protected function getFrameworkStatus(): string
    {
        $checks = [
            $this->checkDatabaseConnection(),
            $this->checkCacheConnection(), 
            $this->checkFilePermissions(),
            $this->checkDiskSpace(),
        ];

        $failedChecks = array_filter($checks, fn($check) => !$check);
        
        if (empty($failedChecks)) {
            return '运行正常';
        } elseif (count($failedChecks) <= 1) {
            return '警告';
        } else {
            return '错误';
        }
    }

    protected function getActiveThemeName(): string
    {
        $activeTheme = Theme::where('status', 'active')->first();
        return $activeTheme ? $activeTheme->name : '未激活主题';
    }

    protected function getPluginHealth(): string
    {
        $total = Plugin::count();
        $error = Plugin::where('status', 'error')->count();
        
        if ($error === 0) {
            return '优秀';
        } elseif ($error / $total <= 0.1) {
            return '良好';
        } else {
            return '需要关注';
        }
    }

    protected function getPluginHealthColor(): string
    {
        $total = Plugin::count();
        $error = Plugin::where('status', 'error')->count();
        
        if ($error === 0) {
            return 'success';
        } elseif ($error / $total <= 0.1) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    protected function getCacheStatus(): string
    {
        try {
            \Cache::put('test_cache', true, 1);
            return \Cache::get('test_cache') === true ? '正常' : '异常';
        } catch (\Exception $e) {
            return '异常';
        }
    }

    protected function getSystemUptime(): string
    {
        // 简单的系统运行时间计算（实际环境中可能需要更复杂的逻辑）
        return '7天12小时';
    }

    protected function getRecentActivity(): array
    {
        $activities = [];

        // 用户相关活动
        $userActivities = $this->getRecentUsersActivity();
        $activities = array_merge($activities, $userActivities);

        // 最近安装的插件
        $recentPlugins = Plugin::orderBy('created_at', 'desc')->limit(3)->get();
        foreach ($recentPlugins as $plugin) {
            $activities[] = [
                'type' => 'plugin_installed',
                'title' => "插件 {$plugin->name} 已安装",
                'description' => $plugin->description,
                'time' => $plugin->created_at->diffForHumans(),
                'icon' => 'bi bi-puzzle-fill',
                'color' => 'primary',
            ];
        }

        // 最近更新的主题
        $recentThemes = Theme::orderBy('updated_at', 'desc')->limit(2)->get();
        foreach ($recentThemes as $theme) {
            $activities[] = [
                'type' => 'theme_updated',
                'title' => "主题 {$theme->name} 已更新",
                'description' => "版本 {$theme->version}",
                'time' => $theme->updated_at->diffForHumans(),
                'icon' => 'bi bi-palette',
                'color' => 'success',
            ];
        }

        // 按时间排序（使用created_at时间戳）
        usort($activities, function($a, $b) {
            // 尝试从不同模型获取时间戳进行比较
            $timeA = 0;
            $timeB = 0;
            
            if (isset($a['type']) && strpos($a['type'], 'user') !== false) {
                $timeA = User::orderBy('created_at', 'desc')->first()->created_at ?? now();
            } elseif (isset($a['type']) && strpos($a['type'], 'admin') !== false) {
                $timeA = AdminUser::orderBy('updated_at', 'desc')->first()->updated_at ?? now();
            } elseif (isset($a['type']) && strpos($a['type'], 'plugin') !== false) {
                $timeA = Plugin::orderBy('created_at', 'desc')->first()->created_at ?? now();
            } elseif (isset($a['type']) && strpos($a['type'], 'theme') !== false) {
                $timeA = Theme::orderBy('updated_at', 'desc')->first()->updated_at ?? now();
            }
            
            if (isset($b['type']) && strpos($b['type'], 'user') !== false) {
                $timeB = User::orderBy('created_at', 'desc')->first()->created_at ?? now();
            } elseif (isset($b['type']) && strpos($b['type'], 'admin') !== false) {
                $timeB = AdminUser::orderBy('updated_at', 'desc')->first()->updated_at ?? now();
            } elseif (isset($b['type']) && strpos($b['type'], 'plugin') !== false) {
                $timeB = Plugin::orderBy('created_at', 'desc')->first()->created_at ?? now();
            } elseif (isset($b['type']) && strpos($b['type'], 'theme') !== false) {
                $timeB = Theme::orderBy('updated_at', 'desc')->first()->updated_at ?? now();
            }
            
            return $timeB <=> $timeA; // 降序排序
        });

        return apply_filters('admin.dashboard.activities', array_slice($activities, 0, 10));
    }

    protected function getQuickActions(): array
    {
        $activeTheme = Theme::where('status', 'active')->first();
        
        if ($activeTheme) {
            // 有激活主题时，显示主题相关的业务操作 + 基础管理
            $actions = apply_filters('theme.dashboard.actions', [
                [
                    'title' => '管理内容',
                    'description' => '由主题提供的内容管理功能',
                    'icon' => 'bi bi-edit',
                    'url' => '#', // 由主题决定
                    'color' => 'primary',
                ],
                [
                    'title' => '查看数据',
                    'description' => '查看网站运营数据和统计',
                    'icon' => 'bi bi-graph-up',
                    'url' => '#', // 由主题决定
                    'color' => 'info',
                ],
            ]);
            
            // 添加基础管理操作
            $actions = array_merge($actions, [
                [
                    'title' => '用户管理',
                    'description' => '管理网站用户和权限',
                    'icon' => 'bi bi-users',
                    'url' => route('admin.users.index'),
                    'color' => 'info',
                ],
                [
                    'title' => '扩展功能',
                    'description' => '安装插件增强网站功能',
                    'icon' => 'bi bi-puzzle-fill',
                    'url' => route('admin.plugins.index'),
                    'color' => 'success',
                ],
                [
                    'title' => '系统设置',
                    'description' => '配置网站基本设置',
                    'icon' => 'bi bi-settings',
                    'url' => route('admin.settings.index'),
                    'color' => 'warning',
                ]
            ]);
        } else {
            // 没有激活主题时，引导用户开始使用
            $actions = [
                [
                    'title' => '选择主题',
                    'description' => '选择网站类型：博客、商城、论坛等',
                    'icon' => 'bi bi-palette',
                    'url' => route('admin.themes.index'),
                    'color' => 'primary',
                ],
                [
                    'title' => '用户管理',
                    'description' => '管理用户账户和权限',
                    'icon' => 'bi bi-users',
                    'url' => route('admin.users.index'),
                    'color' => 'info',
                ],
                [
                    'title' => '浏览插件',
                    'description' => '查看可用的功能扩展插件',
                    'icon' => 'bi bi-puzzle-fill',
                    'url' => route('admin.plugins.index'),
                    'color' => 'success',
                ],
                [
                    'title' => '基础设置',
                    'description' => '配置网站基本信息',
                    'icon' => 'bi bi-settings',
                    'url' => route('admin.settings.index'),
                    'color' => 'warning',
                ],
            ];
        }

        return apply_filters('admin.dashboard.actions', $actions);
    }

    protected function getSystemStatus(): string
    {
        $checks = [
            $this->checkDatabaseConnection(),
            $this->checkCacheConnection(), 
            $this->checkFilePermissions(),
            $this->checkDiskSpace(),
        ];

        $failedChecks = array_filter($checks, fn($check) => !$check);
        
        if (empty($failedChecks)) {
            return '正常';
        } elseif (count($failedChecks) <= 1) {
            return '警告';
        } else {
            return '错误';
        }
    }

    protected function getMemoryUsage(): string
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parseSize(ini_get('memory_limit'));
        
        $percentage = $limit > 0 ? round(($memory / $limit) * 100, 1) : 0;
        
        return $this->formatBytes($memory) . " ({$percentage}%)";
    }

    protected function getStorageUsage(): string
    {
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;
        
        $percentage = $totalSpace > 0 ? round(($usedSpace / $totalSpace) * 100, 1) : 0;
        
        return $this->formatBytes($usedSpace) . " ({$percentage}%)";
    }

    protected function checkDatabaseConnection(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkCacheConnection(): bool
    {
        try {
            \Cache::put('test_connection', true, 1);
            return \Cache::get('test_connection') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkFilePermissions(): bool
    {
        $directories = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework/cache'),
            public_path('uploads'),
        ];

        foreach ($directories as $dir) {
            if (!is_writable($dir)) {
                return false;
            }
        }

        return true;
    }

    protected function checkDiskSpace(): bool
    {
        $freeSpace = disk_free_space(storage_path());
        $minSpace = 100 * 1024 * 1024; // 100MB
        
        return $freeSpace > $minSpace;
    }

    protected function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;

        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    protected function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    protected function getResponseTime(): int
    {
        // 模拟响应时间计算，实际环境中可以通过APM工具获取
        $start = microtime(true);
        \DB::connection()->getPdo(); // 简单的数据库连接测试
        $end = microtime(true);
        
        return round(($end - $start) * 1000); // 转换为毫秒
    }

    protected function getPerformanceTrend(): string
    {
        $responseTime = $this->getResponseTime();
        if ($responseTime < 100) {
            return '优秀';
        } elseif ($responseTime < 300) {
            return '良好';
        } elseif ($responseTime < 500) {
            return '一般';
        } else {
            return '需优化';
        }
    }

    protected function getPerformanceTrendType(): string
    {
        $responseTime = $this->getResponseTime();
        if ($responseTime < 100) {
            return 'success';
        } elseif ($responseTime < 300) {
            return 'info';
        } elseif ($responseTime < 500) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    protected function getMemoryTrend(): string
    {
        $memory = memory_get_usage(true);
        $limit = $this->parseSize(ini_get('memory_limit'));
        $percentage = $limit > 0 ? ($memory / $limit) * 100 : 0;
        
        if ($percentage < 50) {
            return '正常';
        } elseif ($percentage < 75) {
            return '注意';
        } else {
            return '告警';
        }
    }

    protected function getMemoryTrendType(): string
    {
        $memory = memory_get_usage(true);
        $limit = $this->parseSize(ini_get('memory_limit'));
        $percentage = $limit > 0 ? ($memory / $limit) * 100 : 0;
        
        if ($percentage < 50) {
            return 'success';
        } elseif ($percentage < 75) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    protected function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseConnection(),
            'cache' => $this->checkCacheConnection(),
            'permissions' => $this->checkFilePermissions(),
            'disk_space' => $this->checkDiskSpace(),
            'php_version' => version_compare(PHP_VERSION, '8.2.0', '>='),
        ];
    }

    protected function getSystemHealthScore(): int
    {
        $health = $this->getSystemHealth();
        $total = count($health);
        $passed = count(array_filter($health));
        
        return round(($passed / $total) * 100);
    }

    protected function getUserTrend(): string
    {
        $today = User::whereDate('created_at', today())->count();
        $yesterday = User::whereDate('created_at', today()->subDay())->count();
        
        if ($yesterday === 0) {
            return $today > 0 ? "+{$today} 今日" : '暂无新增';
        }
        
        $change = $today - $yesterday;
        if ($change > 0) {
            return "+{$change} 较昨日";
        } elseif ($change < 0) {
            return "{$change} 较昨日";
        } else {
            return '持平';
        }
    }

    protected function getRecentUsersActivity(): array
    {
        $activities = [];
        
        // 最近注册的用户
        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user_registered',
                'title' => "用户 {$user->name} 注册",
                'description' => $user->email,
                'time' => $user->created_at->diffForHumans(),
                'icon' => 'bi bi-person-plus',
                'color' => 'success',
            ];
        }
        
        // 最近更新的管理员
        $recentAdmins = AdminUser::whereDate('updated_at', '>=', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();
            
        foreach ($recentAdmins as $admin) {
            $activities[] = [
                'type' => 'admin_updated',
                'title' => "管理员 {$admin->name} 更新",
                'description' => $admin->is_super_admin ? '超级管理员' : '普通管理员',
                'time' => $admin->updated_at->diffForHumans(),
                'icon' => 'bi bi-shield-check',
                'color' => 'primary',
            ];
        }
        
        return $activities;
    }
}