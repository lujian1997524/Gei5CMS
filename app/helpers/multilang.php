<?php

if (!function_exists('__')) {
    /**
     * 翻译文本
     */
    function __(string $key, array $parameters = [], string $domain = 'default'): string
    {
        return App\Services\MultiLanguageService::translate($key, $parameters, $domain);
    }
}

if (!function_exists('trans')) {
    /**
     * 翻译文本（Laravel兼容）
     */
    function trans(string $key, array $parameters = [], string $domain = 'default'): string
    {
        return App\Services\MultiLanguageService::translate($key, $parameters, $domain);
    }
}

if (!function_exists('trans_choice')) {
    /**
     * 带复数形式的翻译
     */
    function trans_choice(string $key, int $number, array $parameters = [], string $domain = 'default'): string
    {
        $parameters['count'] = $number;
        
        // 通过钩子处理复数形式
        $result = \App\Facades\Hook::applyFilters('multilang.trans_choice', null, [
            'key' => $key,
            'number' => $number,
            'parameters' => $parameters,
            'domain' => $domain
        ]);
        
        // 如果没有插件处理复数，回退到普通翻译
        return $result ?? App\Services\MultiLanguageService::translate($key, $parameters, $domain);
    }
}

if (!function_exists('current_language')) {
    /**
     * 获取当前语言
     */
    function current_language(): string
    {
        return App\Services\MultiLanguageService::getCurrentLanguage();
    }
}

if (!function_exists('supported_languages')) {
    /**
     * 获取支持的语言列表
     */
    function supported_languages(): array
    {
        return App\Services\MultiLanguageService::getSupportedLanguages();
    }
}

if (!function_exists('language_url')) {
    /**
     * 生成指定语言的URL
     */
    function language_url(string $language, string $path = null): string
    {
        return App\Services\MultiLanguageService::getLanguageUrl($language, $path);
    }
}

if (!function_exists('alternate_urls')) {
    /**
     * 获取当前页面的多语言URL映射
     */
    function alternate_urls(): array
    {
        return App\Services\MultiLanguageService::getAlternateUrls();
    }
}