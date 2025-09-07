<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\MultiLanguageService;
use App\Traits\ChecksInstallStatus;

class MultiLanguageServiceProvider extends ServiceProvider
{
    use ChecksInstallStatus;
    public function register(): void
    {
        // 注册多语言服务为单例
        $this->app->singleton(MultiLanguageService::class, function ($app) {
            return new MultiLanguageService();
        });
    }

    public function boot(): void
    {
        // 检查是否在安装过程中
        if ($this->isInstalling()) {
            return;
        }

        // 加载辅助函数
        $this->loadHelpers();
        
        // 注册Blade指令
        $this->registerBladeDirectives();
        
        // 注册多语言钩子
        $this->registerMultiLanguageHooks();
    }

    /**
     * 加载辅助函数
     */
    protected function loadHelpers(): void
    {
        $helpersPath = app_path('helpers/multilang.php');
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }
    
    /**
     * 注册Blade指令
     */
    protected function registerBladeDirectives(): void
    {
        // @lang('key', ['param' => 'value']) 指令
        Blade::directive('lang', function ($expression) {
            return "<?php echo __({$expression}); ?>";
        });
        
        // @trans('key', ['param' => 'value']) 指令
        Blade::directive('trans', function ($expression) {
            return "<?php echo trans({$expression}); ?>";
        });
        
        // @transChoice('key', count, ['param' => 'value']) 指令
        Blade::directive('transChoice', function ($expression) {
            return "<?php echo trans_choice({$expression}); ?>";
        });
        
        // @currentLang 指令 - 显示当前语言代码
        Blade::directive('currentLang', function () {
            return "<?php echo current_language(); ?>";
        });
        
        // @languageSwitcher(['option' => 'value']) 指令
        Blade::directive('languageSwitcher', function ($expression = '[]') {
            return "<?php echo App\\Services\\MultiLanguageService::renderLanguageSwitcher({$expression}); ?>";
        });
        
        // @alternateUrls 指令 - 用于SEO的hreflang标签
        Blade::directive('alternateUrls', function () {
            return "<?php 
                \$alternateUrls = alternate_urls();
                foreach (\$alternateUrls as \$langCode => \$info) {
                    echo '<link rel=\"alternate\" hreflang=\"' . \$langCode . '\" href=\"' . \$info['url'] . '\" />' . PHP_EOL;
                }
            ?>";
        });
        
        // @ifLang('zh-CN') 条件指令
        Blade::directive('ifLang', function ($expression) {
            return "<?php if(current_language() === {$expression}): ?>";
        });
        
        // @endifLang 指令
        Blade::directive('endifLang', function () {
            return '<?php endif; ?>';
        });
        
        // @unlessLang('en-US') 条件指令
        Blade::directive('unlessLang', function ($expression) {
            return "<?php unless(current_language() === {$expression}): ?>";
        });
        
        // @endunlessLang 指令
        Blade::directive('endunlessLang', function () {
            return '<?php endunless; ?>';
        });
    }
    
    /**
     * 注册多语言相关的系统钩子
     */
    protected function registerMultiLanguageHooks(): void
    {
        // 检查是否已注册多语言钩子（防止重复注册）
        try {
            $existingMultilangHooks = \App\Models\Hook::where('plugin_slug', 'core')
                ->where('tag', 'LIKE', 'multilang.%')
                ->count();
            
            if ($existingMultilangHooks > 0) {
                return; // 多语言钩子已注册
            }
        } catch (\Exception $e) {
            // 数据库不可用时跳过检查，直接注册钩子
        }
        
        // 注册语言检测钩子
        do_action('multilang.service_loaded');
        
        // 允许插件注册额外的语言
        add_filter('multilang.supported_languages', function ($languages) {
            // 插件可以通过这个钩子添加新的语言支持
            return $languages;
        }, 10, 'core');
        
        // 允许插件自定义语言检测逻辑
        add_filter('multilang.get_current_language', function ($language) {
            // 插件可以通过这个钩子提供自定义的语言检测逻辑
            return $language;
        }, 10, 'core');
        
        // 允许插件提供翻译服务
        add_filter('multilang.translate', function ($translated, $context) {
            // 插件应该通过这个钩子提供实际的翻译功能
            // $context包含：key, parameters, domain, language, default
            return $translated;
        }, 10, 'core');
        
        // 允许插件提供批量翻译
        add_filter('multilang.translate_batch', function ($translations, $context) {
            // 插件可以通过这个钩子提供批量翻译以提高性能
            // $context包含：keys, domain, language
            return $translations;
        }, 10, 'core');
        
        // 允许插件自定义语言切换器
        add_filter('multilang.render_language_switcher', function ($html, $context) {
            // 插件可以通过这个钩子提供自定义的语言切换器HTML
            // $context包含：current_language, supported_languages, options
            return $html;
        }, 10, 'core');
        
        // 允许插件自定义URL生成
        add_filter('multilang.generate_language_url', function ($url, $context) {
            // 插件可以通过这个钩子自定义多语言URL结构
            // $context包含：language, path, current_language
            return $url;
        }, 10, 'core');
        
        // 允许插件处理复数翻译
        add_filter('multilang.trans_choice', function ($result, $context) {
            // 插件可以通过这个钩子处理复数形式的翻译
            // $context包含：key, number, parameters, domain
            return $result;
        }, 10, 'core');
        
        // 语言切换时的钩子
        add_action('multilang.language_switched', function ($newLanguage) {
            // 插件可以通过这个钩子在语言切换时执行自定义操作
            // 比如清除缓存、重新加载配置等
        }, 10, 'core');
    }
}