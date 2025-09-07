# 多语言系统架构文档

## 概述

Gei5CMS 提供了完整的多语言支持架构，但**不包含具体的翻译实现**。框架只提供钩子、接口和基础设施，具体的翻译功能需要通过插件实现。

## 架构设计理念

- **框架负责**: 语言检测、会话管理、钩子系统、API接口
- **插件负责**: 翻译数据存储、翻译逻辑、语言文件管理

## 核心组件

### 1. MultiLanguageService

核心服务类，提供多语言的基础功能：

```php
// 获取当前语言
$currentLang = MultiLanguageService::getCurrentLanguage();

// 设置语言
MultiLanguageService::setCurrentLanguage('en-US');

// 获取支持的语言
$languages = MultiLanguageService::getSupportedLanguages();

// 翻译文本（通过钩子）
$translated = MultiLanguageService::translate('hello_world', ['name' => 'John']);
```

### 2. 辅助函数

提供Laravel风格的翻译函数：

```php
// 基础翻译
echo __('hello_world');

// 带参数翻译
echo __('welcome_user', ['name' => 'John']);

// 复数翻译
echo trans_choice('items_count', $count, ['count' => $count]);

// 获取当前语言
$lang = current_language();

// 生成语言URL
$url = language_url('en-US', '/about');
```

### 3. Blade指令

在模板中使用多语言：

```blade
{{-- 基础翻译 --}}
@lang('hello_world')

{{-- 带参数翻译 --}}
@trans('welcome_user', ['name' => 'John'])

{{-- 复数翻译 --}}
@transChoice('items_count', $count, ['count' => $count])

{{-- 当前语言代码 --}}
当前语言: @currentLang

{{-- 语言切换器 --}}
@languageSwitcher(['style' => 'dropdown'])

{{-- SEO多语言标签 --}}
@alternateUrls

{{-- 条件语言显示 --}}
@ifLang('zh-CN')
    <p>这是中文内容</p>
@endifLang

@unlessLang('en-US')
    <p>这不是英文页面</p>
@endunlessLang
```

### 4. API接口

前端可通过API与多语言系统交互：

```javascript
// 获取支持的语言
GET /admin/api/language/supported

// 获取当前语言
GET /admin/api/language/current

// 设置语言
POST /admin/api/language/set
{
    "language": "en-US"
}

// 单个翻译
POST /admin/api/language/translate
{
    "key": "hello_world",
    "parameters": {"name": "John"},
    "domain": "frontend"
}

// 批量翻译
POST /admin/api/language/translate-batch
{
    "keys": ["hello_world", "goodbye", "thank_you"],
    "domain": "frontend"
}
```

## 钩子系统

插件通过以下钩子提供翻译功能：

### 过滤器钩子（Filters）

```php
// 1. 支持的语言列表
add_filter('multilang.supported_languages', function($languages) {
    $languages['fr-FR'] = 'Français';
    return $languages;
});

// 2. 语言检测逻辑
add_filter('multilang.get_current_language', function($language) {
    // 自定义语言检测逻辑
    return $customLanguage ?? $language;
});

// 3. 文本翻译（最重要）
add_filter('multilang.translate', function($translated, $context) {
    // $context包含: key, parameters, domain, language, default
    
    // 从数据库/缓存/文件获取翻译
    $translation = YourTranslationPlugin::getTranslation(
        $context['key'], 
        $context['language'], 
        $context['domain']
    );
    
    return $translation ?: $translated;
}, 10, 2);

// 4. 批量翻译（性能优化）
add_filter('multilang.translate_batch', function($translations, $context) {
    // $context包含: keys, domain, language
    
    return YourTranslationPlugin::getTranslations(
        $context['keys'],
        $context['language'],
        $context['domain']
    );
}, 10, 2);

// 5. 复数翻译
add_filter('multilang.trans_choice', function($result, $context) {
    // $context包含: key, number, parameters, domain
    
    return YourTranslationPlugin::handlePlural(
        $context['key'],
        $context['number'],
        $context['parameters']
    );
}, 10, 2);

// 6. 语言切换器HTML
add_filter('multilang.render_language_switcher', function($html, $context) {
    // $context包含: current_language, supported_languages, options
    
    return YourPlugin::renderCustomSwitcher($context);
}, 10, 2);

// 7. 多语言URL生成
add_filter('multilang.generate_language_url', function($url, $context) {
    // $context包含: language, path, current_language
    
    return YourPlugin::generateSEOFriendlyUrl($context);
}, 10, 2);
```

### 动作钩子（Actions）

```php
// 1. 多语言服务加载完成
add_action('multilang.service_loaded', function() {
    YourTranslationPlugin::initialize();
});

// 2. 语言切换时
add_action('multilang.language_switched', function($newLanguage) {
    // 清除翻译缓存
    YourPlugin::clearTranslationCache();
    
    // 重新加载配置
    YourPlugin::reloadConfig($newLanguage);
});

// 3. 语言检测完成
add_action('multilang.language_detected', function($detectedLanguage) {
    YourPlugin::logLanguageDetection($detectedLanguage);
});
```

## 插件实现示例

以下是一个简单翻译插件的实现示例：

```php
class SimpleTranslationPlugin 
{
    public function register() 
    {
        // 注册翻译过滤器
        add_filter('multilang.translate', [$this, 'translate'], 10, 2);
        add_filter('multilang.translate_batch', [$this, 'translateBatch'], 10, 2);
    }
    
    public function translate($translated, $context) 
    {
        $translations = $this->loadTranslations($context['language']);
        
        $key = $context['key'];
        $domain = $context['domain'];
        
        return $translations[$domain][$key] ?? $translated;
    }
    
    public function translateBatch($translations, $context) 
    {
        $languageTranslations = $this->loadTranslations($context['language']);
        $domain = $context['domain'];
        
        foreach ($context['keys'] as $key) {
            $translations[$key] = $languageTranslations[$domain][$key] ?? $key;
        }
        
        return $translations;
    }
    
    private function loadTranslations($language) 
    {
        // 从缓存/数据库/文件加载翻译
        return Cache::remember("translations.{$language}", 3600, function() use ($language) {
            return $this->loadFromDatabase($language);
        });
    }
}
```

## 最佳实践

### 1. 性能优化
- 使用批量翻译API减少数据库查询
- 实现翻译缓存机制
- 懒加载翻译数据

### 2. SEO友好
- 使用hreflang标签
- 生成语言相关的URL结构
- 设置正确的Content-Language响应头

### 3. 用户体验
- 记住用户的语言偏好
- 提供优雅的语言切换界面
- 支持浏览器语言自动检测

## 中间件集成

在需要的路由组中添加语言检测中间件：

```php
Route::middleware(['web', 'detect.language'])->group(function () {
    // 需要多语言支持的路由
});
```

## 前端集成

```javascript
// JavaScript SDK示例
class MultiLangAPI {
    async getCurrentLanguage() {
        const response = await fetch('/admin/api/language/current');
        return await response.json();
    }
    
    async setLanguage(language) {
        const response = await fetch('/admin/api/language/set', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ language })
        });
        return await response.json();
    }
    
    async translate(key, parameters = {}, domain = 'default') {
        const response = await fetch('/admin/api/language/translate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ key, parameters, domain })
        });
        return await response.json();
    }
}
```

## 总结

Gei5CMS的多语言架构提供了完整的基础设施，但将具体实现留给插件。这种设计允许：

1. **灵活性**: 插件可以选择任何翻译存储方式（数据库、文件、API）
2. **性能**: 插件可以实现最适合的缓存策略
3. **扩展性**: 支持复杂的翻译逻辑和自定义功能
4. **兼容性**: 与各种翻译服务和工具集成

通过这个架构，开发者可以创建适合自己需求的翻译插件，而无需修改框架核心代码。