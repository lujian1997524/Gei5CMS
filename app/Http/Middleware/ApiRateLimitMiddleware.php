<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    protected array $defaultLimits = [
        'requests_per_minute' => 60,
        'requests_per_hour' => 3600,
        'requests_per_day' => 86400,
    ];

    public function handle(Request $request, Closure $next, ...$limits): Response
    {
        do_action('api.rate_limit.check', $request);

        $identifier = $this->getIdentifier($request);
        $rateLimits = $this->parseLimits($limits);

        foreach ($rateLimits as $window => $limit) {
            if (!$this->checkRateLimit($identifier, $window, $limit)) {
                return $this->rateLimitExceededResponse($window, $limit);
            }
        }

        // 记录请求
        $this->recordRequest($identifier, $rateLimits);

        // 添加限制头信息
        $response = $next($request);
        $this->addRateLimitHeaders($response, $identifier, $rateLimits);

        do_action('api.rate_limit.passed', $request, $rateLimits);

        return $response;
    }

    protected function getIdentifier(Request $request): string
    {
        // 优先使用API密钥作为标识符
        $apiKeyData = $request->attributes->get('api_key_data');
        if ($apiKeyData && isset($apiKeyData['name'])) {
            return 'api_key:' . $apiKeyData['name'];
        }

        // 使用IP地址作为标识符
        return 'ip:' . $request->ip();
    }

    protected function parseLimits(array $limits): array
    {
        if (empty($limits)) {
            return $this->defaultLimits;
        }

        $parsed = [];
        foreach ($limits as $limit) {
            if (strpos($limit, ':') !== false) {
                [$requests, $window] = explode(':', $limit);
                $parsed[$window] = (int) $requests;
            }
        }

        return !empty($parsed) ? $parsed : $this->defaultLimits;
    }

    protected function checkRateLimit(string $identifier, string $window, int $limit): bool
    {
        $key = $this->getCacheKey($identifier, $window);
        $current = Cache::get($key, 0);

        return $current < $limit;
    }

    protected function recordRequest(string $identifier, array $rateLimits): void
    {
        foreach ($rateLimits as $window => $limit) {
            $key = $this->getCacheKey($identifier, $window);
            $ttl = $this->getWindowTtl($window);

            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, $ttl);
        }
    }

    protected function addRateLimitHeaders(Response $response, string $identifier, array $rateLimits): void
    {
        foreach ($rateLimits as $window => $limit) {
            $key = $this->getCacheKey($identifier, $window);
            $current = Cache::get($key, 0);
            $remaining = max(0, $limit - $current);
            $resetTime = Cache::get($key . ':reset', now()->addSeconds($this->getWindowTtl($window)))->timestamp;

            $response->headers->set("X-RateLimit-Limit-{$window}", $limit);
            $response->headers->set("X-RateLimit-Remaining-{$window}", $remaining);
            $response->headers->set("X-RateLimit-Reset-{$window}", $resetTime);
        }
    }

    protected function getCacheKey(string $identifier, string $window): string
    {
        $timestamp = now();
        
        switch ($window) {
            case 'minute':
            case 'requests_per_minute':
                $period = $timestamp->format('Y-m-d-H-i');
                break;
            case 'hour':
            case 'requests_per_hour':
                $period = $timestamp->format('Y-m-d-H');
                break;
            case 'day':
            case 'requests_per_day':
                $period = $timestamp->format('Y-m-d');
                break;
            default:
                $period = $timestamp->format('Y-m-d-H-i');
        }

        return "rate_limit:{$identifier}:{$window}:{$period}";
    }

    protected function getWindowTtl(string $window): int
    {
        switch ($window) {
            case 'minute':
            case 'requests_per_minute':
                return 60;
            case 'hour':
            case 'requests_per_hour':
                return 3600;
            case 'day':
            case 'requests_per_day':
                return 86400;
            default:
                return 60;
        }
    }

    protected function rateLimitExceededResponse(string $window, int $limit): Response
    {
        Log::warning('API rate limit exceeded', [
            'window' => $window,
            'limit' => $limit,
            'ip' => request()->ip(),
        ]);

        do_action('request.rate_limit.exceeded', request(), $window, $limit);

        $retryAfter = $this->getWindowTtl($window);
        
        return response()->json([
            'success' => false,
            'message' => 'Rate limit exceeded',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'details' => [
                'limit' => $limit,
                'window' => $window,
                'retry_after' => $retryAfter,
            ],
            'timestamp' => now()->toISOString(),
        ], 429)->header('Retry-After', $retryAfter);
    }
}
