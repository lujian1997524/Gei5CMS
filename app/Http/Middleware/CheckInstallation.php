<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 检查是否已安装
        if (!$this->isInstalled()) {
            // 如果已经在安装路由上，允许访问
            if ($request->is('install*')) {
                return $next($request);
            }
            
            // 其他所有请求都重定向到安装页面
            return redirect('/install');
        }
        
        // 已安装但访问安装页面，重定向到首页或管理后台
        if ($request->is('install*')) {
            return redirect('/admin/login');
        }
        
        return $next($request);
    }
    
    /**
     * 检查系统是否已安装
     */
    private function isInstalled(): bool
    {
        // 检查安装锁定文件是否存在
        $lockFile = base_path('storage/installed.lock');
        if (!File::exists($lockFile)) {
            return false;
        }
        
        // 检查 .env 文件是否存在且配置完整
        $envFile = base_path('.env');
        if (!File::exists($envFile)) {
            return false;
        }
        
        // 检查数据库配置是否完整
        $requiredEnvVars = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
        foreach ($requiredEnvVars as $var) {
            if (empty(env($var))) {
                return false;
            }
        }
        
        // 检查数据库连接是否可用
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            return false;
        }
        
        // 检查基础数据表是否存在
        try {
            if (!\Schema::hasTable('admin_users')) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        
        return true;
    }
}