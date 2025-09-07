<?php

// 安装检查脚本 - 在Laravel启动前检查和修复配置
$basePath = __DIR__ . '/..';

// 1. 检查并创建.env文件
if (!file_exists($basePath . '/.env')) {
    $envContent = "APP_NAME=Gei5CMS\n";
    $envContent .= "APP_ENV=local\n";
    $envContent .= "APP_KEY=base64:" . base64_encode(random_bytes(32)) . "\n";
    $envContent .= "APP_DEBUG=true\n";
    $envContent .= "APP_TIMEZONE=Asia/Shanghai\n";
    $envContent .= "APP_URL=\n\n";
    $envContent .= "DB_CONNECTION=mysql\n";
    $envContent .= "DB_HOST=\n";
    $envContent .= "DB_PORT=3306\n";
    $envContent .= "DB_DATABASE=\n";
    $envContent .= "DB_USERNAME=\n";
    $envContent .= "DB_PASSWORD=\n\n";
    $envContent .= "SESSION_DRIVER=file\n";
    $envContent .= "SESSION_LIFETIME=120\n";
    $envContent .= "CACHE_STORE=file\n";
    $envContent .= "QUEUE_CONNECTION=sync\n";
    
    file_put_contents($basePath . '/.env', $envContent);
}

// 2. 检查存储目录权限
$storageDirectories = [
    $basePath . '/storage/logs',
    $basePath . '/storage/framework/cache',
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/bootstrap/cache'
];

foreach ($storageDirectories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

// 3. 检查是否已安装
if (file_exists($basePath . '/storage/installed.lock')) {
    // 已安装，跳转到主页
    if (strpos($_SERVER['REQUEST_URI'], '/install') !== false) {
        header('Location: /admin');
        exit;
    }
}