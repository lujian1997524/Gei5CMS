<?php

namespace App\Traits;

trait ChecksInstallStatus
{
    /**
     * 检查是否正在安装
     */
    protected function isInstalling(): bool
    {
        // 检查是否已安装
        if (!file_exists(base_path('storage/installed.lock'))) {
            return true;
        }

        // 检查是否是安装路由
        $request = $this->app['request'] ?? null;
        if ($request && $request->is('install*')) {
            return true;
        }

        // 检查数据库连接是否可用
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }
}