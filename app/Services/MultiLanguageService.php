<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use App\Facades\Hook;

/**
 * 多语言服务接口
 * 
 * 提供多语言支持的基础架构和钩子系统
 * 具体的翻译功能由插件实现
 */
class MultiLanguageService
{
    protected static string $defaultLanguage = 'zh-CN';
    protected static ?string $currentLanguage = null;
    protected static array $supportedLanguages = [];
    
    /**
     * 获取当前语言
     */
    public static function getCurrentLanguage(): string
    {
        if (static::$currentLanguage === null) {
            // 通过钩子获取当前语言，允许插件自定义逻辑
            $language = Hook::applyFilters('multilang.get_current_language', null);
            
            if (!$language) {
                // 默认逻辑：Session > Cookie > Browser > Default
                $language = Session::get('app_language') 
                         ?? Cookie::get('app_language')
                         ?? static::detectBrowserLanguage()
                         ?? static::$defaultLanguage;
            }
            
            static::$currentLanguage = $language;
        }
        
        return static::$currentLanguage;
    }
    
    /**
     * 设置当前语言
     */
    public static function setCurrentLanguage(string $language): void
    {
        if (static::isLanguageSupported($language)) {
            static::$currentLanguage = $language;
            
            // 保存到会话和cookie
            Session::put('app_language', $language);
            Cookie::queue('app_language', $language, 60 * 24 * 365); // 1年
            
            // 触发语言切换钩子，允许插件执行自定义操作
            Hook::doAction('multilang.language_switched', $language);
        }
    }
    
    /**
     * 获取支持的语言列表
     */
    public static function getSupportedLanguages(): array
    {
        if (empty(static::$supportedLanguages)) {
            // 通过钩子获取支持的语言，允许插件注册新语言
            static::$supportedLanguages = Hook::applyFilters('multilang.supported_languages', [
                'zh-CN' => '简体中文',
                'zh-TW' => '繁體中文',
                'en-US' => 'English',
                'ja-JP' => '日本語',
                'ko-KR' => '한국어',
            ]);
        }
        
        return static::$supportedLanguages;
    }
    
    /**
     * 检查语言是否支持
     */
    public static function isLanguageSupported(string $language): bool
    {
        return array_key_exists($language, static::getSupportedLanguages());
    }
    
    /**
     * 翻译文本 - 通过钩子实现
     */
    public static function translate(string $key, array $parameters = [], string $domain = 'default'): string
    {
        $currentLanguage = static::getCurrentLanguage();
        
        // 通过钩子过滤器进行翻译，具体实现由插件提供
        $translated = Hook::applyFilters('multilang.translate', $key, [
            'key' => $key,
            'parameters' => $parameters,
            'domain' => $domain,
            'language' => $currentLanguage,
            'default' => $key
        ]);
        
        // 如果没有插件提供翻译，返回原始键值
        if ($translated === $key && $currentLanguage !== static::$defaultLanguage) {
            // 尝试获取默认语言的翻译
            $translated = Hook::applyFilters('multilang.translate', $key, [
                'key' => $key,
                'parameters' => $parameters,
                'domain' => $domain,
                'language' => static::$defaultLanguage,
                'default' => $key
            ]);
        }
        
        // 处理参数替换
        if (!empty($parameters) && is_array($parameters)) {
            foreach ($parameters as $param => $value) {
                $translated = str_replace(":$param", $value, $translated);
            }
        }
        
        return $translated;
    }
    
    /**
     * 批量翻译
     */
    public static function translateBatch(array $keys, string $domain = 'default'): array
    {
        $currentLanguage = static::getCurrentLanguage();
        
        // 通过钩子进行批量翻译，提高性能
        $translations = Hook::applyFilters('multilang.translate_batch', [], [
            'keys' => $keys,
            'domain' => $domain,
            'language' => $currentLanguage
        ]);
        
        // 如果插件没有提供批量翻译，回退到单个翻译
        if (empty($translations)) {
            $translations = [];
            foreach ($keys as $key) {
                $translations[$key] = static::translate($key, [], $domain);
            }
        }
        
        return $translations;
    }
    
    /**
     * 获取语言切换器HTML - 通过钩子实现
     */
    public static function renderLanguageSwitcher(array $options = []): string
    {
        $currentLanguage = static::getCurrentLanguage();
        $supportedLanguages = static::getSupportedLanguages();
        
        // 通过钩子渲染语言切换器，允许插件自定义样式和行为
        $html = Hook::applyFilters('multilang.render_language_switcher', '', [
            'current_language' => $currentLanguage,
            'supported_languages' => $supportedLanguages,
            'options' => $options
        ]);
        
        // 如果没有插件提供渲染，返回默认的简单HTML
        if (empty($html)) {
            $html = static::renderDefaultLanguageSwitcher($currentLanguage, $supportedLanguages, $options);
        }
        
        return $html;
    }
    
    /**
     * 获取语言相关的URL
     */
    public static function getLanguageUrl(string $language, string $path = null): string
    {
        $path = $path ?? request()->path();
        
        // 通过钩子生成语言URL，允许插件自定义URL结构
        $url = Hook::applyFilters('multilang.generate_language_url', null, [
            'language' => $language,
            'path' => $path,
            'current_language' => static::getCurrentLanguage()
        ]);
        
        // 如果插件没有提供URL生成，使用默认逻辑
        if (!$url) {
            $url = url($path . '?lang=' . $language);
        }
        
        return $url;
    }
    
    /**
     * 获取当前页面的多语言URL映射
     */
    public static function getAlternateUrls(): array
    {
        $urls = [];
        $currentPath = request()->path();
        
        foreach (static::getSupportedLanguages() as $langCode => $langName) {
            $urls[$langCode] = [
                'code' => $langCode,
                'name' => $langName,
                'url' => static::getLanguageUrl($langCode, $currentPath)
            ];
        }
        
        // 允许插件修改多语言URL映射
        return Hook::applyFilters('multilang.alternate_urls', $urls, $currentPath);
    }
    
    /**
     * 检测浏览器首选语言
     */
    protected static function detectBrowserLanguage(): ?string
    {
        $acceptLanguage = request()->header('Accept-Language');
        if (!$acceptLanguage) {
            return null;
        }
        
        $supportedLanguages = array_keys(static::getSupportedLanguages());
        
        // 解析Accept-Language头
        preg_match_all('/([a-z]{2}(-[A-Z]{2})?)(;q=([0-9\.]+))?/', $acceptLanguage, $matches);
        
        $languages = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $lang = $matches[1][$i];
            $quality = isset($matches[4][$i]) ? (float)$matches[4][$i] : 1.0;
            $languages[$lang] = $quality;
        }
        
        // 按质量排序
        arsort($languages);
        
        // 找到第一个支持的语言
        foreach ($languages as $lang => $quality) {
            if (in_array($lang, $supportedLanguages)) {
                return $lang;
            }
            
            // 尝试匹配语言族（如en匹配en-US）
            $langFamily = substr($lang, 0, 2);
            foreach ($supportedLanguages as $supported) {
                if (substr($supported, 0, 2) === $langFamily) {
                    return $supported;
                }
            }
        }
        
        return null;
    }
    
    /**
     * 默认的语言切换器渲染
     */
    protected static function renderDefaultLanguageSwitcher(string $currentLanguage, array $supportedLanguages, array $options): string
    {
        $html = '<div class="language-switcher">';
        $html .= '<select onchange="window.location.href=this.value">';
        
        foreach ($supportedLanguages as $code => $name) {
            $selected = $code === $currentLanguage ? 'selected' : '';
            $url = static::getLanguageUrl($code);
            $html .= "<option value=\"{$url}\" {$selected}>{$name}</option>";
        }
        
        $html .= '</select>';
        $html .= '</div>';
        
        return $html;
    }
}