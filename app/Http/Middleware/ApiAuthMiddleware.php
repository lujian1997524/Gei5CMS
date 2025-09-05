<?php

namespace App\Http\Middleware;

use App\Services\ApiManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    protected ApiManager $apiManager;

    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function handle(Request $request, Closure $next, string $type = 'required'): Response
    {
        do_action('api.auth.check', $request);

        // 检查API密钥
        $apiKey = $this->extractApiKey($request);
        
        if (!$apiKey) {
            if ($type === 'optional') {
                return $next($request);
            }
            
            return $this->unauthorizedResponse('API key is required');
        }

        // 验证API密钥
        $validation = $this->apiManager->validateApiKey($apiKey);
        
        if (!$validation['valid']) {
            Log::warning('Invalid API key used', [
                'key' => substr($apiKey, 0, 10) . '...',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return $this->unauthorizedResponse($validation['error']);
        }

        // 将API密钥信息附加到请求
        $request->attributes->set('api_key_data', $validation['data']);
        
        // 记录API访问
        $this->logApiAccess($request, $validation['data']);

        do_action('api.auth.success', $request, $validation['data']);

        return $next($request);
    }

    protected function extractApiKey(Request $request): ?string
    {
        // 从多个位置提取API密钥
        $apiKey = null;

        // 1. Authorization Header (Bearer token)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $apiKey = substr($authHeader, 7);
        }

        // 2. X-API-Key Header
        if (!$apiKey) {
            $apiKey = $request->header('X-API-Key');
        }

        // 3. Query parameter
        if (!$apiKey) {
            $apiKey = $request->query('api_key');
        }

        // 4. Form parameter (POST requests)
        if (!$apiKey && $request->isMethod('POST')) {
            $apiKey = $request->input('api_key');
        }

        return $apiKey;
    }

    protected function logApiAccess(Request $request, array $keyData): void
    {
        Log::info('API access', [
            'api_key_name' => $keyData['name'],
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
            'timestamp' => now()->toISOString(),
        ], 401);
    }
}
