<?php

namespace App\Services;

use App\Contracts\PluginInterface;
use App\Models\Plugin;
use App\Models\Hook;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class PluginManager
{
    protected array $loadedPlugins = [];
    protected array $activePlugins = [];
    protected string $pluginsPath;

    public function __construct()
    {
        $this->pluginsPath = base_path('plugins');
    }

    public function discoverPlugins(): array
    {
        if (!File::exists($this->pluginsPath)) {
            File::makeDirectory($this->pluginsPath, 0755, true);
            return [];
        }

        $plugins = [];
        $directories = File::directories($this->pluginsPath);

        foreach ($directories as $directory) {
            $pluginFile = $directory . '/plugin.php';
            if (File::exists($pluginFile)) {
                try {
                    $pluginClass = $this->loadPluginClass($directory);
                    if ($pluginClass) {
                        $plugins[] = $pluginClass;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to load plugin from {$directory}: " . $e->getMessage());
                }
            }
        }

        return $plugins;
    }

    public function installPlugin(string $pluginSlug, PluginInterface $plugin): bool
    {
        try {
            // 检查依赖
            if (!$this->checkDependencies($plugin)) {
                throw new \Exception("Plugin dependencies not met");
            }

            // 创建数据库记录
            $pluginModel = Plugin::create([
                'slug' => $pluginSlug,
                'name' => $plugin->getName(),
                'version' => $plugin->getVersion(),
                'description' => $plugin->getDescription(),
                'author' => $plugin->getAuthor(),
                'status' => 'installed',
                'config' => json_encode($plugin->getConfigSchema()),
                'dependencies' => json_encode($plugin->getDependencies()),
                'service_type' => $plugin->getServiceType(),
            ]);

            // 注册钩子
            $this->registerPluginHooks($pluginSlug, $plugin);

            // 执行插件安装逻辑
            if (!$plugin->install()) {
                throw new \Exception("Plugin installation failed");
            }

            // 更新状态
            $pluginModel->update(['status' => 'inactive']);

            Log::info("Plugin {$pluginSlug} installed successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Plugin installation failed: " . $e->getMessage());
            
            // 清理失败的安装
            Plugin::where('slug', $pluginSlug)->delete();
            $this->unregisterPluginHooks($pluginSlug);
            
            return false;
        }
    }

    public function activatePlugin(string $pluginSlug): bool
    {
        try {
            $pluginModel = Plugin::where('slug', $pluginSlug)->first();
            if (!$pluginModel) {
                throw new \Exception("Plugin not found");
            }

            $plugin = $this->getPluginInstance($pluginSlug);
            if (!$plugin) {
                throw new \Exception("Plugin class not found");
            }

            if (!$plugin->isCompatible()) {
                throw new \Exception("Plugin is not compatible with current system");
            }

            if (!$plugin->activate()) {
                throw new \Exception("Plugin activation failed");
            }

            $pluginModel->update(['status' => 'active']);
            $this->activePlugins[$pluginSlug] = $plugin;

            Log::info("Plugin {$pluginSlug} activated successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Plugin activation failed: " . $e->getMessage());
            return false;
        }
    }

    public function deactivatePlugin(string $pluginSlug): bool
    {
        try {
            $pluginModel = Plugin::where('slug', $pluginSlug)->first();
            if (!$pluginModel) {
                throw new \Exception("Plugin not found");
            }

            $plugin = $this->getPluginInstance($pluginSlug);
            if ($plugin && !$plugin->deactivate()) {
                throw new \Exception("Plugin deactivation failed");
            }

            $pluginModel->update(['status' => 'inactive']);
            unset($this->activePlugins[$pluginSlug]);

            Log::info("Plugin {$pluginSlug} deactivated successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Plugin deactivation failed: " . $e->getMessage());
            return false;
        }
    }

    public function uninstallPlugin(string $pluginSlug): bool
    {
        try {
            // 先停用插件
            $this->deactivatePlugin($pluginSlug);

            $plugin = $this->getPluginInstance($pluginSlug);
            if ($plugin && !$plugin->uninstall()) {
                throw new \Exception("Plugin uninstall failed");
            }

            // 清理数据库记录
            Plugin::where('slug', $pluginSlug)->delete();
            $this->unregisterPluginHooks($pluginSlug);

            Log::info("Plugin {$pluginSlug} uninstalled successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Plugin uninstall failed: " . $e->getMessage());
            return false;
        }
    }

    public function loadActivePlugins(): void
    {
        $activePlugins = Plugin::active()->get();

        foreach ($activePlugins as $pluginModel) {
            try {
                $plugin = $this->getPluginInstance($pluginModel->slug);
                if ($plugin && $plugin->isCompatible()) {
                    $plugin->boot();
                    $this->activePlugins[$pluginModel->slug] = $plugin;
                    $this->loadedPlugins[$pluginModel->slug] = $plugin;
                }
            } catch (\Exception $e) {
                Log::error("Failed to load plugin {$pluginModel->slug}: " . $e->getMessage());
            }
        }
    }

    public function getActivePlugins(): array
    {
        return $this->activePlugins;
    }

    public function getPluginInstance(string $pluginSlug): ?PluginInterface
    {
        if (isset($this->loadedPlugins[$pluginSlug])) {
            return $this->loadedPlugins[$pluginSlug];
        }

        $pluginPath = $this->pluginsPath . '/' . $pluginSlug;
        return $this->loadPluginClass($pluginPath);
    }

    protected function loadPluginClass(string $pluginPath): ?PluginInterface
    {
        $pluginFile = $pluginPath . '/plugin.php';
        if (!File::exists($pluginFile)) {
            return null;
        }

        // 沙箱加载插件
        $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
        
        try {
            require_once $pluginFile;
            
            // 获取插件类名
            $composerFile = $pluginPath . '/composer.json';
            if (File::exists($composerFile)) {
                $composer = json_decode(File::get($composerFile), true);
                if (isset($composer['extra']['plugin-class'])) {
                    $className = $composer['extra']['plugin-class'];
                    if (class_exists($className)) {
                        $plugin = new $className();
                        if ($plugin instanceof PluginInterface) {
                            return $plugin;
                        }
                    }
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Failed to load plugin class: " . $e->getMessage());
            return null;
        } finally {
            error_reporting($oldErrorReporting);
        }
    }

    protected function checkDependencies(PluginInterface $plugin): bool
    {
        $dependencies = $plugin->getDependencies();
        
        foreach ($dependencies as $dependency => $version) {
            if ($dependency === 'php') {
                if (version_compare(PHP_VERSION, $version, '<')) {
                    return false;
                }
                continue;
            }

            if ($dependency === 'laravel') {
                if (version_compare(app()->version(), $version, '<')) {
                    return false;
                }
                continue;
            }

            // 检查其他插件依赖
            $dependentPlugin = Plugin::where('slug', $dependency)
                ->where('status', 'active')
                ->first();
                
            if (!$dependentPlugin) {
                return false;
            }

            if (version_compare($dependentPlugin->version, $version, '<')) {
                return false;
            }
        }

        return true;
    }

    protected function registerPluginHooks(string $pluginSlug, PluginInterface $plugin): void
    {
        $hooks = $plugin->getProvidedHooks();
        
        foreach ($hooks as $hookData) {
            Hook::create([
                'tag' => $hookData['tag'],
                'callback' => $hookData['callback'],
                'priority' => $hookData['priority'] ?? 10,
                'plugin_slug' => $pluginSlug,
                'hook_type' => $hookData['type'] ?? 'action',
                'is_active' => true,
            ]);
        }
    }

    protected function unregisterPluginHooks(string $pluginSlug): void
    {
        Hook::where('plugin_slug', $pluginSlug)->delete();
    }
}