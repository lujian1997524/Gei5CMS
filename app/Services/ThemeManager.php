<?php

namespace App\Services;

use App\Contracts\ThemeInterface;
use App\Models\Theme;
use App\Models\ThemeCustomizer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

class ThemeManager
{
    protected array $loadedThemes = [];
    protected ?ThemeInterface $activeTheme = null;
    protected string $themesPath;
    protected array $businessTables = [];

    public function __construct()
    {
        $this->themesPath = base_path('themes');
    }

    public function discoverThemes(): array
    {
        if (!File::exists($this->themesPath)) {
            File::makeDirectory($this->themesPath, 0755, true);
            return [];
        }

        $themes = [];
        $directories = File::directories($this->themesPath);

        foreach ($directories as $directory) {
            $themeFile = $directory . '/theme.php';
            if (File::exists($themeFile)) {
                try {
                    $themeClass = $this->loadThemeClass($directory);
                    if ($themeClass) {
                        $themes[] = $themeClass;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to load theme from {$directory}: " . $e->getMessage());
                }
            }
        }

        return $themes;
    }

    public function installTheme(string $themeSlug, ThemeInterface $theme): bool
    {
        try {
            // 检查应用类型冲突
            $existingActiveTheme = Theme::where('status', 'active')->first();
            if ($existingActiveTheme && $existingActiveTheme->application_type !== $theme->getApplicationType()) {
                throw new \Exception("Cannot install theme with different application type. Current: {$existingActiveTheme->application_type}, New: {$theme->getApplicationType()}");
            }

            // 创建数据库记录
            $themeModel = Theme::create([
                'slug' => $themeSlug,
                'name' => $theme->getName(),
                'version' => $theme->getVersion(),
                'description' => $theme->getDescription(),
                'author' => $theme->getAuthor(),
                'application_type' => $theme->getApplicationType(),
                'table_schema' => json_encode($theme->getTableSchema()),
                'required_plugins' => json_encode($theme->getRequiredPlugins()),
                'default_settings' => json_encode($theme->getDefaultSettings()),
                'status' => 'inactive',
            ]);

            // 执行主题安装逻辑
            if (!$theme->install()) {
                throw new \Exception("Theme installation failed");
            }

            // 保存默认设置
            $this->saveDefaultSettings($themeSlug, $theme->getDefaultSettings());

            Log::info("Theme {$themeSlug} installed successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Theme installation failed: " . $e->getMessage());
            
            // 清理失败的安装
            Theme::where('slug', $themeSlug)->delete();
            $this->cleanupThemeSettings($themeSlug);
            
            return false;
        }
    }

    public function activateTheme(string $themeSlug): bool
    {
        try {
            $themeModel = Theme::where('slug', $themeSlug)->first();
            if (!$themeModel) {
                throw new \Exception("Theme not found");
            }

            $theme = $this->getThemeInstance($themeSlug);
            if (!$theme) {
                throw new \Exception("Theme class not found");
            }

            if (!$theme->isCompatible()) {
                throw new \Exception("Theme is not compatible with current system");
            }

            // 检查必需插件
            if (!$this->checkRequiredPlugins($theme)) {
                throw new \Exception("Required plugins are not active");
            }

            // 停用当前主题
            $currentActiveTheme = Theme::where('status', 'active')->first();
            if ($currentActiveTheme && $currentActiveTheme->slug !== $themeSlug) {
                $this->deactivateTheme($currentActiveTheme->slug);
            }

            // 创建业务表
            if (!$this->createBusinessTables($theme)) {
                throw new \Exception("Failed to create business tables");
            }

            // 激活主题
            if (!$theme->activate()) {
                throw new \Exception("Theme activation failed");
            }

            // 更新数据库状态
            $themeModel->update(['status' => 'active']);
            
            // 注册主题路由和视图
            $this->registerThemeRoutes($theme);
            $this->registerThemeViews($theme);

            $this->activeTheme = $theme;

            Log::info("Theme {$themeSlug} activated successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Theme activation failed: " . $e->getMessage());
            
            // 清理失败的激活
            $this->rollbackBusinessTables($themeSlug);
            
            return false;
        }
    }

    public function deactivateTheme(string $themeSlug): bool
    {
        try {
            $themeModel = Theme::where('slug', $themeSlug)->first();
            if (!$themeModel) {
                throw new \Exception("Theme not found");
            }

            $theme = $this->getThemeInstance($themeSlug);
            if ($theme && !$theme->deactivate()) {
                throw new \Exception("Theme deactivation failed");
            }

            // 更新数据库状态
            $themeModel->update(['status' => 'inactive']);
            
            // 清理路由和视图
            $this->unregisterThemeRoutes($themeSlug);
            $this->unregisterThemeViews($themeSlug);

            if ($this->activeTheme && $this->getThemeSlug($this->activeTheme) === $themeSlug) {
                $this->activeTheme = null;
            }

            Log::info("Theme {$themeSlug} deactivated successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Theme deactivation failed: " . $e->getMessage());
            return false;
        }
    }

    public function uninstallTheme(string $themeSlug): bool
    {
        try {
            // 先停用主题
            $this->deactivateTheme($themeSlug);

            $theme = $this->getThemeInstance($themeSlug);
            
            // 删除业务表（需要用户确认）
            if ($theme && !$this->dropBusinessTables($theme)) {
                Log::warning("Failed to drop business tables for theme {$themeSlug}");
            }

            // 执行主题卸载逻辑
            if ($theme && !$theme->uninstall()) {
                throw new \Exception("Theme uninstall failed");
            }

            // 清理数据库记录
            Theme::where('slug', $themeSlug)->delete();
            $this->cleanupThemeSettings($themeSlug);

            Log::info("Theme {$themeSlug} uninstalled successfully");
            return true;

        } catch (\Exception $e) {
            Log::error("Theme uninstall failed: " . $e->getMessage());
            return false;
        }
    }

    public function getActiveTheme(): ?ThemeInterface
    {
        if ($this->activeTheme) {
            return $this->activeTheme;
        }

        $activeThemeModel = Theme::where('status', 'active')->first();
        if ($activeThemeModel) {
            $this->activeTheme = $this->getThemeInstance($activeThemeModel->slug);
        }

        return $this->activeTheme;
    }

    public function getThemeInstance(string $themeSlug): ?ThemeInterface
    {
        if (isset($this->loadedThemes[$themeSlug])) {
            return $this->loadedThemes[$themeSlug];
        }

        $themePath = $this->themesPath . '/' . $themeSlug;
        $theme = $this->loadThemeClass($themePath);
        
        if ($theme) {
            $this->loadedThemes[$themeSlug] = $theme;
        }

        return $theme;
    }

    public function getThemeCustomization(string $themeSlug, string $key = null, $default = null)
    {
        $customizations = ThemeCustomizer::where('theme_slug', $themeSlug)->get();
        $config = [];

        foreach ($customizations as $item) {
            $config[$item->setting_key] = $item->setting_value;
        }

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    public function setThemeCustomization(string $themeSlug, string $key, $value): void
    {
        ThemeCustomizer::updateOrCreate(
            [
                'theme_slug' => $themeSlug,
                'setting_key' => $key,
            ],
            [
                'setting_value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
            ]
        );
    }

    protected function loadThemeClass(string $themePath): ?ThemeInterface
    {
        $themeFile = $themePath . '/theme.php';
        if (!File::exists($themeFile)) {
            return null;
        }

        try {
            require_once $themeFile;
            
            // 获取主题类名
            $composerFile = $themePath . '/composer.json';
            if (File::exists($composerFile)) {
                $composer = json_decode(File::get($composerFile), true);
                if (isset($composer['extra']['theme-class'])) {
                    $className = $composer['extra']['theme-class'];
                    if (class_exists($className)) {
                        $theme = new $className();
                        if ($theme instanceof ThemeInterface) {
                            return $theme;
                        }
                    }
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Failed to load theme class: " . $e->getMessage());
            return null;
        }
    }

    protected function checkRequiredPlugins(ThemeInterface $theme): bool
    {
        $requiredPlugins = $theme->getRequiredPlugins();
        
        foreach ($requiredPlugins as $plugin => $version) {
            $pluginModel = \App\Models\Plugin::where('slug', $plugin)
                ->where('status', 'active')
                ->first();
                
            if (!$pluginModel) {
                return false;
            }

            if (version_compare($pluginModel->version, $version, '<')) {
                return false;
            }
        }

        return true;
    }

    protected function createBusinessTables(ThemeInterface $theme): bool
    {
        try {
            $tableSchema = $theme->getTableSchema();
            
            foreach ($tableSchema as $tableName => $columns) {
                if (!Schema::hasTable($tableName)) {
                    Schema::create($tableName, function ($table) use ($columns) {
                        foreach ($columns as $column) {
                            $this->addColumn($table, $column);
                        }
                    });
                    
                    $this->businessTables[] = $tableName;
                }
            }

            return $theme->createBusinessTables();
            
        } catch (\Exception $e) {
            Log::error("Failed to create business tables: " . $e->getMessage());
            $this->rollbackBusinessTables($theme->getSlug());
            return false;
        }
    }

    protected function dropBusinessTables(ThemeInterface $theme): bool
    {
        try {
            $result = $theme->dropBusinessTables();
            
            $tableSchema = $theme->getTableSchema();
            foreach (array_keys($tableSchema) as $tableName) {
                if (Schema::hasTable($tableName)) {
                    Schema::dropIfExists($tableName);
                }
            }

            return $result;
            
        } catch (\Exception $e) {
            Log::error("Failed to drop business tables: " . $e->getMessage());
            return false;
        }
    }

    protected function rollbackBusinessTables(string $themeSlug): void
    {
        foreach ($this->businessTables as $tableName) {
            try {
                Schema::dropIfExists($tableName);
            } catch (\Exception $e) {
                Log::error("Failed to rollback table {$tableName}: " . $e->getMessage());
            }
        }
        $this->businessTables = [];
    }

    protected function addColumn($table, array $columnDef): void
    {
        $type = $columnDef['type'];
        $name = $columnDef['name'];
        $options = $columnDef['options'] ?? [];

        switch ($type) {
            case 'string':
                $column = $table->string($name, $options['length'] ?? 255);
                break;
            case 'text':
                $column = $table->text($name);
                break;
            case 'integer':
                $column = $table->integer($name);
                break;
            case 'bigInteger':
                $column = $table->bigInteger($name);
                break;
            case 'boolean':
                $column = $table->boolean($name);
                break;
            case 'timestamp':
                $column = $table->timestamp($name);
                break;
            case 'json':
                $column = $table->json($name);
                break;
            default:
                $column = $table->string($name);
        }

        if ($options['nullable'] ?? false) {
            $column->nullable();
        }
        
        if (isset($options['default'])) {
            $column->default($options['default']);
        }
        
        if ($options['index'] ?? false) {
            $column->index();
        }
    }

    protected function saveDefaultSettings(string $themeSlug, array $defaultSettings): void
    {
        foreach ($defaultSettings as $key => $value) {
            $this->setThemeCustomization($themeSlug, $key, $value);
        }
    }

    protected function cleanupThemeSettings(string $themeSlug): void
    {
        ThemeCustomizer::where('theme_slug', $themeSlug)->delete();
    }

    protected function registerThemeRoutes(ThemeInterface $theme): void
    {
        $routes = $theme->getRoutes();
        
        foreach ($routes as $route) {
            Route::match(
                $route['methods'] ?? ['GET'],
                $route['uri'],
                $route['action']
            )->name($route['name'] ?? '');
        }
    }

    protected function registerThemeViews(ThemeInterface $theme): void
    {
        $viewPaths = $theme->getViewPaths();
        
        foreach ($viewPaths as $namespace => $path) {
            View::addNamespace($namespace, $path);
        }
    }

    protected function unregisterThemeRoutes(string $themeSlug): void
    {
        // Laravel doesn't support dynamic route unregistration
        // This would typically require a cache clear
    }

    protected function unregisterThemeViews(string $themeSlug): void
    {
        // Laravel doesn't support dynamic view namespace unregistration
        // This would typically require an application restart
    }

    protected function getThemeSlug(ThemeInterface $theme): string
    {
        return $theme->getSlug();
    }
}