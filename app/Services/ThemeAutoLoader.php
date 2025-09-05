<?php

namespace App\Services;

use App\Models\Theme;
use App\Contracts\ThemeInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThemeAutoLoader
{
    protected ThemeManager $themeManager;
    protected string $themesPath;
    protected ?ThemeInterface $activeTheme = null;

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
        $this->themesPath = base_path('themes');
    }

    public function boot(): void
    {
        $this->createThemesDirectory();
        $this->autoDiscoverThemes();
        $this->loadActiveTheme();
    }

    public function loadActiveTheme(): void
    {
        $activeThemeModel = Theme::where('status', 'active')->first();
        
        if ($activeThemeModel) {
            try {
                $theme = $this->themeManager->getThemeInstance($activeThemeModel->slug);
                if ($theme && $theme->isCompatible()) {
                    $theme->boot();
                    $this->activeTheme = $theme;
                    $this->registerThemeAssets($theme);
                    $this->registerThemeMiddleware($theme);
                }
            } catch (\Exception $e) {
                Log::error("Failed to load active theme {$activeThemeModel->slug}: " . $e->getMessage());
            }
        }
    }

    public function autoDiscoverThemes(): void
    {
        if (!File::exists($this->themesPath)) {
            return;
        }

        $discoveredThemes = [];
        $directories = File::directories($this->themesPath);

        foreach ($directories as $directory) {
            $themeSlug = basename($directory);
            $composerFile = $directory . '/composer.json';
            
            if (File::exists($composerFile)) {
                try {
                    $composer = json_decode(File::get($composerFile), true);
                    if ($this->isValidThemeStructure($composer, $directory)) {
                        $discoveredThemes[$themeSlug] = $this->parseThemeMetadata($composer, $directory);
                    }
                } catch (\Exception $e) {
                    Log::warning("无法解析主题 {$themeSlug}: " . $e->getMessage());
                }
            }
        }

        $this->syncDiscoveredThemes($discoveredThemes);
    }

    public function getActiveTheme(): ?ThemeInterface
    {
        return $this->activeTheme;
    }

    public function getApplicationType(): string
    {
        if ($this->activeTheme) {
            return $this->activeTheme->getApplicationType();
        }

        $activeThemeModel = Theme::where('status', 'active')->first();
        return $activeThemeModel ? $activeThemeModel->application_type : 'general';
    }

    public function isThemeActive(string $themeSlug): bool
    {
        return $this->activeTheme && $this->activeTheme->getSlug() === $themeSlug;
    }

    public function getThemeAssets(string $themeSlug): array
    {
        $theme = $this->themeManager->getThemeInstance($themeSlug);
        if (!$theme) {
            return [];
        }

        return $theme->getAssetPaths();
    }

    public function switchApplicationType(string $newType): bool
    {
        // 检查是否有适合的主题
        $compatibleThemes = Theme::where('application_type', $newType)
            ->where('status', '!=', 'broken')
            ->get();

        if ($compatibleThemes->isEmpty()) {
            return false;
        }

        // 停用当前主题
        $currentTheme = Theme::where('status', 'active')->first();
        if ($currentTheme) {
            $this->themeManager->deactivateTheme($currentTheme->slug);
        }

        // 激活新类型的默认主题
        $defaultTheme = $compatibleThemes->first();
        return $this->themeManager->activateTheme($defaultTheme->slug);
    }

    protected function createThemesDirectory(): void
    {
        if (!File::exists($this->themesPath)) {
            File::makeDirectory($this->themesPath, 0755, true);
        }
    }

    protected function isValidThemeStructure(array $composer, string $directory): bool
    {
        // 检查必要的字段
        $requiredFields = ['name', 'description', 'version', 'type'];
        foreach ($requiredFields as $field) {
            if (!isset($composer[$field])) {
                return false;
            }
        }

        // 检查主题类型
        if ($composer['type'] !== 'gei5-theme') {
            return false;
        }

        // 检查主题类
        if (!isset($composer['extra']['theme-class'])) {
            return false;
        }

        // 检查应用类型
        if (!isset($composer['extra']['application-type'])) {
            return false;
        }

        // 检查必要文件
        $requiredFiles = ['theme.php'];
        foreach ($requiredFiles as $file) {
            if (!File::exists($directory . '/' . $file)) {
                return false;
            }
        }

        return true;
    }

    protected function parseThemeMetadata(array $composer, string $directory): array
    {
        $slug = basename($directory);
        
        return [
            'slug' => $slug,
            'name' => $composer['name'],
            'description' => $composer['description'] ?? '',
            'version' => $composer['version'],
            'author' => $composer['authors'][0]['name'] ?? 'Unknown',
            'theme_class' => $composer['extra']['theme-class'],
            'application_type' => $composer['extra']['application-type'],
            'table_schema' => $composer['extra']['table-schema'] ?? [],
            'required_plugins' => $composer['require'] ?? [],
            'default_settings' => $composer['extra']['default-settings'] ?? [],
        ];
    }

    protected function syncDiscoveredThemes(array $discoveredThemes): void
    {
        foreach ($discoveredThemes as $slug => $metadata) {
            $existingTheme = Theme::where('slug', $slug)->first();
            
            if (!$existingTheme) {
                Log::info("发现新主题: {$slug}");
                continue; // 新主题需要手动安装
            }

            // 检查版本更新
            if (version_compare($metadata['version'], $existingTheme->version, '>')) {
                Log::info("主题 {$slug} 有新版本: {$metadata['version']}");
                
                // 可以在这里触发更新提醒
                $existingTheme->update([
                    'has_update' => true,
                    'available_version' => $metadata['version'],
                ]);
            }
        }
    }

    protected function registerThemeAssets(ThemeInterface $theme): void
    {
        $assetPaths = $theme->getAssetPaths();
        
        // 这里可以注册主题的CSS和JS文件到Laravel的资产管理
        foreach ($assetPaths as $type => $path) {
            switch ($type) {
                case 'css':
                    $this->registerCssAssets($path);
                    break;
                case 'js':
                    $this->registerJsAssets($path);
                    break;
                case 'images':
                    $this->registerImageAssets($path);
                    break;
            }
        }
    }

    protected function registerThemeMiddleware(ThemeInterface $theme): void
    {
        $middleware = $theme->getMiddleware();
        
        foreach ($middleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                app('router')->aliasMiddleware(
                    'theme.' . $theme->getSlug(),
                    $middlewareClass
                );
            }
        }
    }

    protected function registerCssAssets(string $path): void
    {
        // 注册CSS资源
        if (File::exists($path)) {
            // 这里可以整合Vite或其他资产构建工具
        }
    }

    protected function registerJsAssets(string $path): void
    {
        // 注册JavaScript资源
        if (File::exists($path)) {
            // 这里可以整合Vite或其他资产构建工具
        }
    }

    protected function registerImageAssets(string $path): void
    {
        // 注册图像资源
        if (File::exists($path)) {
            // 可以设置public路径映射
        }
    }
}