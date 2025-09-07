<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MultiLanguageService;
use Symfony\Component\HttpFoundation\Response;

class DetectLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 检查URL中是否有语言参数
        if ($request->has('lang')) {
            $language = $request->get('lang');
            if (MultiLanguageService::isLanguageSupported($language)) {
                MultiLanguageService::setCurrentLanguage($language);
            }
        }
        
        // 确保当前语言已设置
        MultiLanguageService::getCurrentLanguage();
        
        // 触发语言检测完成钩子
        do_action('multilang.language_detected', MultiLanguageService::getCurrentLanguage());
        
        $response = $next($request);
        
        // 在响应头中添加当前语言信息
        $response->headers->set('Content-Language', MultiLanguageService::getCurrentLanguage());
        
        return $response;
    }
}