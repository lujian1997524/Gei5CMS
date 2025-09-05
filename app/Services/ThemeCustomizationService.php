<?php

namespace App\Services;

use App\Models\Theme;
use App\Models\ThemeCustomizer;
use App\Contracts\ThemeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ThemeCustomizationService
{
    protected ThemeManager $themeManager;
    protected array $cachedCustomizations = [];

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    public function getCustomizationConfig(string $themeSlug): array
    {
        $theme = $this->themeManager->getThemeInstance($themeSlug);
        if (!$theme) {
            return [];
        }

        return $theme->getCustomizerConfig();
    }

    public function getCustomizations(string $themeSlug): array
    {
        if (isset($this->cachedCustomizations[$themeSlug])) {
            return $this->cachedCustomizations[$themeSlug];
        }

        $cacheKey = "theme_customizations_{$themeSlug}";
        $customizations = Cache::remember($cacheKey, 3600, function () use ($themeSlug) {
            $data = ThemeCustomizer::where('theme_slug', $themeSlug)->get();
            $result = [];
            
            foreach ($data as $item) {
                $value = $item->setting_value;
                
                // 尝试解析JSON
                if ($this->isJson($value)) {
                    $value = json_decode($value, true);
                }
                
                $result[$item->setting_key] = $value;
            }
            
            return $result;
        });

        $this->cachedCustomizations[$themeSlug] = $customizations;
        return $customizations;
    }

    public function setCustomization(string $themeSlug, string $key, $value): bool
    {
        try {
            // 验证设置key是否合法
            if (!$this->isValidCustomizationKey($themeSlug, $key)) {
                throw new \InvalidArgumentException("Invalid customization key: {$key}");
            }

            // 验证设置值
            if (!$this->validateCustomizationValue($themeSlug, $key, $value)) {
                throw new \InvalidArgumentException("Invalid customization value for key: {$key}");
            }

            // 保存到数据库
            ThemeCustomizer::updateOrCreate(
                [
                    'theme_slug' => $themeSlug,
                    'setting_key' => $key,
                ],
                [
                    'setting_value' => is_array($value) || is_object($value) 
                        ? json_encode($value) 
                        : (string) $value,
                ]
            );

            // 清除缓存
            $this->clearCustomizationCache($themeSlug);

            Log::info("Theme customization updated: {$themeSlug}.{$key}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to set theme customization: " . $e->getMessage());
            return false;
        }
    }

    public function getCustomization(string $themeSlug, string $key, $default = null)
    {
        $customizations = $this->getCustomizations($themeSlug);
        return data_get($customizations, $key, $default);
    }

    public function resetCustomization(string $themeSlug, string $key = null): bool
    {
        try {
            if ($key) {
                // 重置单个设置
                $theme = $this->themeManager->getThemeInstance($themeSlug);
                if ($theme) {
                    $defaultSettings = $theme->getDefaultSettings();
                    $defaultValue = data_get($defaultSettings, $key);
                    
                    if ($defaultValue !== null) {
                        $this->setCustomization($themeSlug, $key, $defaultValue);
                    } else {
                        ThemeCustomizer::where('theme_slug', $themeSlug)
                            ->where('setting_key', $key)
                            ->delete();
                    }
                }
            } else {
                // 重置所有设置
                ThemeCustomizer::where('theme_slug', $themeSlug)->delete();
                
                // 恢复默认设置
                $this->restoreDefaultSettings($themeSlug);
            }

            $this->clearCustomizationCache($themeSlug);
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to reset theme customization: " . $e->getMessage());
            return false;
        }
    }

    public function exportCustomizations(string $themeSlug): array
    {
        $customizations = $this->getCustomizations($themeSlug);
        $theme = Theme::where('slug', $themeSlug)->first();

        return [
            'theme_slug' => $themeSlug,
            'theme_name' => $theme ? $theme->name : '',
            'theme_version' => $theme ? $theme->version : '',
            'exported_at' => now()->toISOString(),
            'customizations' => $customizations,
        ];
    }

    public function importCustomizations(string $themeSlug, array $data): bool
    {
        try {
            if (!isset($data['customizations']) || !is_array($data['customizations'])) {
                throw new \InvalidArgumentException("Invalid import data format");
            }

            // 验证主题兼容性
            if (isset($data['theme_slug']) && $data['theme_slug'] !== $themeSlug) {
                Log::warning("Importing customizations from different theme: {$data['theme_slug']} to {$themeSlug}");
            }

            foreach ($data['customizations'] as $key => $value) {
                $this->setCustomization($themeSlug, $key, $value);
            }

            Log::info("Theme customizations imported successfully for: {$themeSlug}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to import theme customizations: " . $e->getMessage());
            return false;
        }
    }

    public function validateCustomizations(string $themeSlug, array $customizations): array
    {
        $errors = [];
        $config = $this->getCustomizationConfig($themeSlug);

        foreach ($customizations as $key => $value) {
            if (!$this->isValidCustomizationKey($themeSlug, $key)) {
                $errors[$key] = "Invalid customization key";
                continue;
            }

            if (!$this->validateCustomizationValue($themeSlug, $key, $value)) {
                $errors[$key] = "Invalid customization value";
            }
        }

        return $errors;
    }

    public function getCustomizationSchema(string $themeSlug): array
    {
        $config = $this->getCustomizationConfig($themeSlug);
        $schema = [];

        foreach ($config as $section => $fields) {
            if (!is_array($fields)) continue;

            $schema[$section] = [];
            foreach ($fields as $fieldKey => $fieldConfig) {
                $schema[$section][$fieldKey] = [
                    'type' => $fieldConfig['type'] ?? 'text',
                    'label' => $fieldConfig['label'] ?? $fieldKey,
                    'description' => $fieldConfig['description'] ?? '',
                    'default' => $fieldConfig['default'] ?? null,
                    'options' => $fieldConfig['options'] ?? [],
                    'validation' => $fieldConfig['validation'] ?? [],
                    'required' => $fieldConfig['required'] ?? false,
                ];
            }
        }

        return $schema;
    }

    protected function isValidCustomizationKey(string $themeSlug, string $key): bool
    {
        $config = $this->getCustomizationConfig($themeSlug);
        
        // 检查key是否在配置中定义
        foreach ($config as $section => $fields) {
            if (is_array($fields) && array_key_exists($key, $fields)) {
                return true;
            }
        }

        return false;
    }

    protected function validateCustomizationValue(string $themeSlug, string $key, $value): bool
    {
        $config = $this->getCustomizationConfig($themeSlug);
        $fieldConfig = null;

        // 找到字段配置
        foreach ($config as $section => $fields) {
            if (is_array($fields) && isset($fields[$key])) {
                $fieldConfig = $fields[$key];
                break;
            }
        }

        if (!$fieldConfig) {
            return false;
        }

        // 基本类型验证
        $type = $fieldConfig['type'] ?? 'text';
        if (!$this->validateFieldType($value, $type)) {
            return false;
        }

        // 自定义验证规则
        if (isset($fieldConfig['validation'])) {
            $validator = Validator::make(
                [$key => $value],
                [$key => $fieldConfig['validation']]
            );

            return !$validator->fails();
        }

        return true;
    }

    protected function validateFieldType($value, string $type): bool
    {
        switch ($type) {
            case 'text':
            case 'textarea':
            case 'select':
                return is_string($value) || is_numeric($value);
            
            case 'number':
                return is_numeric($value);
            
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false']);
            
            case 'color':
                return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
            
            case 'image':
            case 'file':
                return is_string($value); // 文件路径
            
            case 'array':
                return is_array($value);
            
            case 'json':
                return is_array($value) || is_object($value) || $this->isJson($value);
            
            default:
                return true;
        }
    }

    protected function restoreDefaultSettings(string $themeSlug): void
    {
        $theme = $this->themeManager->getThemeInstance($themeSlug);
        if (!$theme) {
            return;
        }

        $defaultSettings = $theme->getDefaultSettings();
        foreach ($defaultSettings as $key => $value) {
            $this->setCustomization($themeSlug, $key, $value);
        }
    }

    protected function clearCustomizationCache(string $themeSlug): void
    {
        $cacheKey = "theme_customizations_{$themeSlug}";
        Cache::forget($cacheKey);
        unset($this->cachedCustomizations[$themeSlug]);
    }

    protected function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}