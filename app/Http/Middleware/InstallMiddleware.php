<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class InstallMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 如果是安装路由，允许通过
        if ($request->is('install*')) {
            return $next($request);
        }

        // 检查是否已安装
        if (!File::exists(base_path('storage/installed.lock'))) {
            return redirect('/install');
        }

        return $next($request);
    }
}