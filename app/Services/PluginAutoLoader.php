<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\PluginData;
use App\Contracts\PluginInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class PluginAutoLoader
{
    protected PluginManager $pluginManager;
    protected string $pluginsPath;
    protected array $loadedPlugins = [];
    protected array $pluginConfigs = [];

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
        $this->pluginsPath = base_path('plugins');
    }

    public function boot(): void
    {
        $this->createPluginsDirectory();
        $this->loadPluginConfigurations();
        $this->autoDiscoverPlugins();
        $this->loadActivePlugins();
    }

    public function autoDiscoverPlugins(): void
    {
        if (!File::exists($this->pluginsPath)) {
            return;
        }

        $discoveredPlugins = [];
        $directories = File::directories($this->pluginsPath);

        foreach ($directories as $directory) {
            $pluginSlug = basename($directory);
            $composerFile = $directory . '/composer.json';
            
            if (File::exists($composerFile)) {
                try {
                    $composer = json_decode(File::get($composerFile), true);
                    if ($this->isValidPluginStructure($composer, $directory)) {
                        $discoveredPlugins[$pluginSlug] = $this->parsePluginMetadata($composer, $directory);
                    }
                } catch (\Exception $e) {
                    Log::warning("无法解析插件 {$pluginSlug}: " . $e->getMessage());
                }
            }
        }

        $this->syncDiscoveredPlugins($discoveredPlugins);
    }

    public function loadActivePlugins(): void
    {
        try {
            $activePlugins = Plugin::active()->orderBy('priority')->get();
        } catch (\Exception $e) {
            // 数据库不可用时跳过活动插件加载
            \Illuminate\Support\Facades\Log::debug('Active plugins loading skipped due to database unavailability: ' . $e->getMessage());
            return;
        }

        foreach ($activePlugins as $pluginModel) {
            try {
                $this->loadPlugin($pluginModel);
            } catch (\Exception $e) {
                Log::error("加载插件 {$pluginModel->slug} 失败: " . $e->getMessage());
                
                // 自动禁用有问题的插件
                try {
                    $pluginModel->update(['status' => 'broken']);
                } catch (\Exception $updateException) {
                    // 如果更新也失败（比如数据库问题），记录日志但不中断
                    Log::error("无法更新插件状态: " . $updateException->getMessage());
                }
            }
        }
    }

    public function loadPlugin(Plugin $pluginModel): void
    {
        if (isset($this->loadedPlugins[$pluginModel->slug])) {
            return;
        }

        $pluginPath = $this->pluginsPath . '/' . $pluginModel->slug;
        
        if (!File::exists($pluginPath)) {
            throw new \Exception("插件目录不存在: {$pluginPath}");
        }

        // 加载插件类
        $plugin = $this->instantiatePlugin($pluginModel->slug, $pluginPath);
        if (!$plugin) {
            throw new \Exception("无法实例化插件类");
        }

        // 检查兼容性
        if (!$plugin->isCompatible()) {
            throw new \Exception("插件不兼容当前系统");
        }

        // 加载插件配置
        $this->loadPluginConfiguration($pluginModel->slug);

        // 启动插件
        $plugin->boot();

        $this->loadedPlugins[$pluginModel->slug] = $plugin;
        Log::info("插件 {$pluginModel->slug} 加载成功");
    }

    public function getPluginConfig(string $pluginSlug, string $key = null, $default = null)
    {
        if (!isset($this->pluginConfigs[$pluginSlug])) {
            $this->loadPluginConfiguration($pluginSlug);
        }

        $config = $this->pluginConfigs[$pluginSlug] ?? [];

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    public function setPluginConfig(string $pluginSlug, string $key, $value): void
    {
        if (!isset($this->pluginConfigs[$pluginSlug])) {
            $this->pluginConfigs[$pluginSlug] = [];
        }

        data_set($this->pluginConfigs[$pluginSlug], $key, $value);

        // 保存到数据库
        PluginData::updateOrCreate(
            [
                'plugin_slug' => $pluginSlug,
                'data_key' => $key,
            ],
            [
                'data_value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
            ]
        );

        // 清除缓存
        Cache::forget("plugin_config_{$pluginSlug}");
    }

    public function reloadPlugin(string $pluginSlug): bool
    {
        try {
            // 停用插件
            if (isset($this->loadedPlugins[$pluginSlug])) {
                $plugin = $this->loadedPlugins[$pluginSlug];
                if ($plugin instanceof PluginInterface) {
                    $plugin->deactivate();
                }
                unset($this->loadedPlugins[$pluginSlug]);
            }

            // 重新加载插件
            try {
                $pluginModel = Plugin::where('slug', $pluginSlug)->first();
            } catch (\Exception $e) {
                // 数据库不可用时跳过重新加载
                Log::debug("Plugin reload skipped due to database unavailability: " . $e->getMessage());
                return false;
            }
            
            if ($pluginModel && $pluginModel->status === 'active') {
                $this->loadPlugin($pluginModel);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("重载插件 {$pluginSlug} 失败: " . $e->getMessage());
            return false;
        }
    }

    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    public function isPluginLoaded(string $pluginSlug): bool
    {
        return isset($this->loadedPlugins[$pluginSlug]);
    }

    protected function createPluginsDirectory(): void
    {
        if (!File::exists($this->pluginsPath)) {
            File::makeDirectory($this->pluginsPath, 0755, true);
        }
    }

    protected function loadPluginConfigurations(): void
    {
        // 检查数据库是否可用（安装期间跳过）
        try {
            $plugins = Plugin::all();
        } catch (\Exception $e) {
            // 数据库不可用时跳过插件配置加载
            \Illuminate\Support\Facades\Log::debug('Plugin configurations skipped due to database unavailability: ' . $e->getMessage());
            return;
        }

        foreach ($plugins as $plugin) {
            $this->loadPluginConfiguration($plugin->slug);
        }
    }

    protected function loadPluginConfiguration(string $pluginSlug): void
    {
        if (isset($this->pluginConfigs[$pluginSlug])) {
            return;
        }

        // 尝试从缓存加载
        $cacheKey = "plugin_config_{$pluginSlug}";
        $cachedConfig = Cache::get($cacheKey);

        if ($cachedConfig !== null) {
            $this->pluginConfigs[$pluginSlug] = $cachedConfig;
            return;
        }

        // 从数据库加载
        $configData = PluginData::where('plugin_slug', $pluginSlug)->get();
        $config = [];

        foreach ($configData as $item) {
            $value = $item->data_value;
            
            // 尝试解析JSON
            if (is_string($value) && $this->isJson($value)) {
                $value = json_decode($value, true);
            }
            
            data_set($config, $item->data_key, $value);
        }

        $this->pluginConfigs[$pluginSlug] = $config;
        
        // 缓存配置
        Cache::put($cacheKey, $config, now()->addHours(1));
    }

    protected function instantiatePlugin(string $pluginSlug, string $pluginPath): ?PluginInterface
    {
        $composerFile = $pluginPath . '/composer.json';
        if (!File::exists($composerFile)) {
            return null;
        }

        $composer = json_decode(File::get($composerFile), true);
        if (!isset($composer['extra']['plugin-class'])) {
            return null;
        }

        $className = $composer['extra']['plugin-class'];

        // 加载插件的autoload文件
        $autoloadFile = $pluginPath . '/vendor/autoload.php';
        if (File::exists($autoloadFile)) {
            require_once $autoloadFile;
        }

        // 加载插件主文件
        $pluginFile = $pluginPath . '/plugin.php';
        if (File::exists($pluginFile)) {
            require_once $pluginFile;
        }

        if (!class_exists($className)) {
            return null;
        }

        $plugin = new $className();
        return $plugin instanceof PluginInterface ? $plugin : null;
    }

    protected function isValidPluginStructure(array $composer, string $directory): bool
    {
        // 检查必要的字段
        $requiredFields = ['name', 'description', 'version', 'type'];
        foreach ($requiredFields as $field) {
            if (!isset($composer[$field])) {
                return false;
            }
        }

        // 检查插件类型
        if ($composer['type'] !== 'gei5-plugin') {
            return false;
        }

        // 检查插件类
        if (!isset($composer['extra']['plugin-class'])) {
            return false;
        }

        // 检查必要文件
        $requiredFiles = ['plugin.php'];
        foreach ($requiredFiles as $file) {
            if (!File::exists($directory . '/' . $file)) {
                return false;
            }
        }

        return true;
    }

    protected function parsePluginMetadata(array $composer, string $directory): array
    {
        $slug = basename($directory);
        
        return [
            'slug' => $slug,
            'name' => $composer['name'],
            'description' => $composer['description'] ?? '',
            'version' => $composer['version'],
            'author' => $composer['authors'][0]['name'] ?? 'Unknown',
            'plugin_class' => $composer['extra']['plugin-class'],
            'service_type' => $composer['extra']['service-type'] ?? 'general',
            'dependencies' => $composer['require'] ?? [],
            'config_schema' => $composer['extra']['config-schema'] ?? [],
        ];
    }

    protected function syncDiscoveredPlugins(array $discoveredPlugins): void
    {
        foreach ($discoveredPlugins as $slug => $metadata) {
            try {
                $existingPlugin = Plugin::where('slug', $slug)->first();
            } catch (\Exception $e) {
                // 数据库不可用时跳过插件同步
                Log::debug("Plugin sync skipped due to database unavailability: " . $e->getMessage());
                continue;
            }
            
            if (!$existingPlugin) {
                Log::info("发现新插件: {$slug}");
                continue; // 新插件需要手动安装
            }

            // 检查版本更新
            if (version_compare($metadata['version'], $existingPlugin->version, '>')) {
                Log::info("插件 {$slug} 有新版本: {$metadata['version']}");
                
                // 可以在这里触发更新提醒
                $existingPlugin->update([
                    'has_update' => true,
                    'available_version' => $metadata['version'],
                ]);
            }
        }
    }

    protected function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}