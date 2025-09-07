<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\MultiLanguageService;

class LanguageController extends Controller
{
    /**
     * 获取支持的语言列表
     */
    public function getSupportedLanguages(): JsonResponse
    {
        return response()->json([
            'languages' => MultiLanguageService::getSupportedLanguages(),
            'current' => MultiLanguageService::getCurrentLanguage(),
        ]);
    }
    
    /**
     * 设置当前语言
     */
    public function setLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'language' => 'required|string|max:10'
        ]);
        
        $language = $request->input('language');
        
        if (!MultiLanguageService::isLanguageSupported($language)) {
            return response()->json([
                'error' => 'Unsupported language',
                'supported_languages' => array_keys(MultiLanguageService::getSupportedLanguages())
            ], 400);
        }
        
        MultiLanguageService::setCurrentLanguage($language);
        
        return response()->json([
            'success' => true,
            'language' => $language,
            'message' => __('language_switched_successfully')
        ]);
    }
    
    /**
     * 获取当前语言
     */
    public function getCurrentLanguage(): JsonResponse
    {
        return response()->json([
            'language' => MultiLanguageService::getCurrentLanguage()
        ]);
    }
    
    /**
     * 批量翻译文本
     */
    public function translateBatch(Request $request): JsonResponse
    {
        $request->validate([
            'keys' => 'required|array',
            'keys.*' => 'string',
            'domain' => 'sometimes|string|max:100'
        ]);
        
        $keys = $request->input('keys');
        $domain = $request->input('domain', 'default');
        
        $translations = MultiLanguageService::translateBatch($keys, $domain);
        
        return response()->json([
            'translations' => $translations,
            'language' => MultiLanguageService::getCurrentLanguage(),
            'domain' => $domain
        ]);
    }
    
    /**
     * 单个文本翻译
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'parameters' => 'sometimes|array',
            'domain' => 'sometimes|string|max:100'
        ]);
        
        $key = $request->input('key');
        $parameters = $request->input('parameters', []);
        $domain = $request->input('domain', 'default');
        
        $translation = MultiLanguageService::translate($key, $parameters, $domain);
        
        return response()->json([
            'key' => $key,
            'translation' => $translation,
            'language' => MultiLanguageService::getCurrentLanguage(),
            'domain' => $domain
        ]);
    }
    
    /**
     * 获取多语言URL映射
     */
    public function getAlternateUrls(Request $request): JsonResponse
    {
        $path = $request->input('path', request()->path());
        
        return response()->json([
            'urls' => MultiLanguageService::getAlternateUrls(),
            'current_path' => $path
        ]);
    }
}