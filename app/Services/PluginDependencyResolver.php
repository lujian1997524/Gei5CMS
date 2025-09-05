<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PluginDependencyResolver
{
    protected array $dependencyGraph = [];
    protected array $resolvedOrder = [];
    protected array $visiting = [];
    protected array $visited = [];

    public function resolveDependencies(array $plugins): array
    {
        $this->buildDependencyGraph($plugins);
        $this->resolvedOrder = [];
        $this->visiting = [];
        $this->visited = [];

        foreach ($this->dependencyGraph as $plugin => $dependencies) {
            if (!in_array($plugin, $this->visited)) {
                $this->topologicalSort($plugin);
            }
        }

        return array_reverse($this->resolvedOrder);
    }

    public function checkDependencyConflicts(string $pluginSlug, array $dependencies): array
    {
        $conflicts = [];

        foreach ($dependencies as $depName => $depVersion) {
            if ($depName === 'php') {
                if (!version_compare(PHP_VERSION, $depVersion, '>=')) {
                    $conflicts[] = [
                        'type' => 'php_version',
                        'required' => $depVersion,
                        'current' => PHP_VERSION,
                        'message' => "需要PHP版本 {$depVersion}，当前版本 " . PHP_VERSION
                    ];
                }
                continue;
            }

            if ($depName === 'laravel') {
                $currentVersion = app()->version();
                if (!version_compare($currentVersion, $depVersion, '>=')) {
                    $conflicts[] = [
                        'type' => 'laravel_version',
                        'required' => $depVersion,
                        'current' => $currentVersion,
                        'message' => "需要Laravel版本 {$depVersion}，当前版本 {$currentVersion}"
                    ];
                }
                continue;
            }

            // 检查插件依赖
            $dependentPlugin = Plugin::where('slug', $depName)->first();
            
            if (!$dependentPlugin) {
                $conflicts[] = [
                    'type' => 'missing_plugin',
                    'plugin' => $depName,
                    'required_version' => $depVersion,
                    'message' => "缺少依赖插件: {$depName}"
                ];
                continue;
            }

            if (!version_compare($dependentPlugin->version, $depVersion, '>=')) {
                $conflicts[] = [
                    'type' => 'plugin_version',
                    'plugin' => $depName,
                    'required' => $depVersion,
                    'current' => $dependentPlugin->version,
                    'message' => "插件 {$depName} 版本不匹配，需要 {$depVersion}，当前 {$dependentPlugin->version}"
                ];
            }

            if ($dependentPlugin->status !== 'active') {
                $conflicts[] = [
                    'type' => 'plugin_inactive',
                    'plugin' => $depName,
                    'message' => "依赖插件 {$depName} 未激活"
                ];
            }
        }

        return $conflicts;
    }

    public function getInstallOrder(array $pluginSlugs): array
    {
        $plugins = [];
        $allDependencies = [];

        // 收集所有插件及其依赖
        foreach ($pluginSlugs as $slug) {
            $plugin = Plugin::where('slug', $slug)->first();
            if ($plugin) {
                $plugins[$slug] = $plugin;
                $dependencies = json_decode($plugin->dependencies, true) ?? [];
                $allDependencies[$slug] = array_keys($dependencies);
            }
        }

        // 解析安装顺序
        return $this->resolveDependencies($allDependencies);
    }

    public function canUninstall(string $pluginSlug): array
    {
        $dependents = $this->findDependents($pluginSlug);
        $conflicts = [];

        foreach ($dependents as $dependent) {
            $plugin = Plugin::where('slug', $dependent)->where('status', 'active')->first();
            if ($plugin) {
                $conflicts[] = [
                    'type' => 'dependent_plugin',
                    'plugin' => $dependent,
                    'message' => "插件 {$dependent} 依赖于 {$pluginSlug}"
                ];
            }
        }

        return $conflicts;
    }

    public function findDependents(string $pluginSlug): array
    {
        $dependents = [];
        $allPlugins = Plugin::all();

        foreach ($allPlugins as $plugin) {
            $dependencies = json_decode($plugin->dependencies, true) ?? [];
            if (array_key_exists($pluginSlug, $dependencies)) {
                $dependents[] = $plugin->slug;
            }
        }

        return $dependents;
    }

    public function validateDependencyGraph(array $dependencies): array
    {
        $this->buildDependencyGraph($dependencies);
        return $this->detectCircularDependencies();
    }

    protected function buildDependencyGraph(array $dependencies): void
    {
        $this->dependencyGraph = [];

        foreach ($dependencies as $plugin => $deps) {
            $this->dependencyGraph[$plugin] = is_array($deps) ? $deps : [];
        }
    }

    protected function topologicalSort(string $plugin): void
    {
        if (in_array($plugin, $this->visiting)) {
            throw new \RuntimeException("检测到循环依赖: {$plugin}");
        }

        if (in_array($plugin, $this->visited)) {
            return;
        }

        $this->visiting[] = $plugin;

        $dependencies = $this->dependencyGraph[$plugin] ?? [];
        foreach ($dependencies as $dependency) {
            if (isset($this->dependencyGraph[$dependency])) {
                $this->topologicalSort($dependency);
            }
        }

        $this->visited[] = $plugin;
        $this->resolvedOrder[] = $plugin;

        // 从visiting中移除
        $index = array_search($plugin, $this->visiting);
        if ($index !== false) {
            array_splice($this->visiting, $index, 1);
        }
    }

    protected function detectCircularDependencies(): array
    {
        $cycles = [];
        $this->visiting = [];
        $this->visited = [];

        foreach ($this->dependencyGraph as $plugin => $dependencies) {
            if (!in_array($plugin, $this->visited)) {
                try {
                    $this->detectCycles($plugin, []);
                } catch (\RuntimeException $e) {
                    $cycles[] = $e->getMessage();
                }
            }
        }

        return $cycles;
    }

    protected function detectCycles(string $plugin, array $path): void
    {
        if (in_array($plugin, $path)) {
            $cycleStart = array_search($plugin, $path);
            $cycle = array_slice($path, $cycleStart);
            $cycle[] = $plugin;
            throw new \RuntimeException("循环依赖: " . implode(' -> ', $cycle));
        }

        if (in_array($plugin, $this->visited)) {
            return;
        }

        $path[] = $plugin;
        $dependencies = $this->dependencyGraph[$plugin] ?? [];

        foreach ($dependencies as $dependency) {
            if (isset($this->dependencyGraph[$dependency])) {
                $this->detectCycles($dependency, $path);
            }
        }

        $this->visited[] = $plugin;
    }

    public function getOptimalInstallOrder(array $pluginSlugs): array
    {
        // 获取所有插件的详细信息
        $pluginDetails = [];
        $allDependencies = [];

        foreach ($pluginSlugs as $slug) {
            $plugin = Plugin::where('slug', $slug)->first();
            if (!$plugin) continue;

            $pluginDetails[$slug] = $plugin;
            $dependencies = json_decode($plugin->dependencies, true) ?? [];
            
            // 只保留插件依赖，过滤系统依赖
            $pluginDeps = [];
            foreach ($dependencies as $depName => $depVersion) {
                if (!in_array($depName, ['php', 'laravel'])) {
                    $pluginDeps[] = $depName;
                }
            }
            
            $allDependencies[$slug] = $pluginDeps;
        }

        try {
            return $this->resolveDependencies($allDependencies);
        } catch (\RuntimeException $e) {
            Log::error("依赖解析失败: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateDependencyReport(string $pluginSlug): array
    {
        $plugin = Plugin::where('slug', $pluginSlug)->first();
        if (!$plugin) {
            return ['error' => '插件不存在'];
        }

        $dependencies = json_decode($plugin->dependencies, true) ?? [];
        $dependents = $this->findDependents($pluginSlug);

        return [
            'plugin' => $plugin->toArray(),
            'direct_dependencies' => $dependencies,
            'dependents' => $dependents,
            'dependency_conflicts' => $this->checkDependencyConflicts($pluginSlug, $dependencies),
            'can_uninstall' => empty($this->canUninstall($pluginSlug)),
        ];
    }
}