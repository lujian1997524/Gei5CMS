<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Models\AdminUser;

class InstallController extends Controller
{
    private $steps = [
        1 => '环境检测',
        2 => '数据库配置', 
        3 => '管理员设置',
        4 => '站点配置',
        5 => '安装完成'
    ];

    /**
     * 检查是否已安装
     */
    public function __construct()
    {
        // 如果没有.env文件，创建最小安装用配置
        if (!file_exists(base_path('.env'))) {
            $this->createMinimalEnvFile();
        }

        if ($this->isInstalled() && !request()->is('install/complete')) {
            abort(404);
        }
    }

    /**
     * 创建最小.env文件用于安装
     */
    private function createMinimalEnvFile()
    {
        $envTemplate = base_path('.env.install');
        $envPath = base_path('.env');
        
        if (file_exists($envTemplate)) {
            copy($envTemplate, $envPath);
        } else {
            // 如果模板不存在，创建基础配置
            $content = "APP_NAME=Gei5CMS\n";
            $content .= "APP_ENV=local\n";
            $content .= "APP_KEY=base64:fzmHgRLw/i+kG7dvZcAnuSiT1QnLzaGvb3/eP2BrSmQ=\n";
            $content .= "APP_DEBUG=true\n";
            $content .= "APP_TIMEZONE=Asia/Shanghai\n";
            $content .= "APP_URL=\n\n";
            $content .= "DB_CONNECTION=mysql\n";
            $content .= "DB_HOST=\n";
            $content .= "DB_PORT=3306\n";
            $content .= "DB_DATABASE=\n";
            $content .= "DB_USERNAME=\n";
            $content .= "DB_PASSWORD=\n\n";
            $content .= "SESSION_DRIVER=file\n";
            $content .= "SESSION_LIFETIME=120\n";
            $content .= "CACHE_STORE=file\n";
            $content .= "QUEUE_CONNECTION=sync\n";
            
            File::put($envPath, $content);
        }
    }

    /**
     * 安装首页
     */
    public function index()
    {
        return view('install.welcome', [
            'title' => 'Gei5CMS 安装向导'
        ]);
    }

    /**
     * 步骤页面
     */
    public function step(int $step)
    {
        if ($step < 1 || $step > 5) {
            return redirect()->route('install.index');
        }

        $method = 'step' . $step;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return redirect()->route('install.index');
    }

    /**
     * 步骤1：环境检测
     */
    private function step1()
    {
        $requirements = $this->checkRequirements();
        $permissions = $this->checkPermissions();
        
        $canContinue = $requirements['success'] && $permissions['success'];

        return view('install.step1', compact('requirements', 'permissions', 'canContinue'));
    }

    /**
     * 步骤2：数据库配置
     */
    private function step2()
    {
        return view('install.step2');
    }

    /**
     * 处理数据库配置
     */
    public function handleDatabaseConfig(Request $request)
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_name' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        try {
            // 测试数据库连接
            $this->testDatabaseConnection(
                $request->db_host,
                $request->db_port,
                $request->db_name,
                $request->db_username,
                $request->db_password
            );

            // 更新.env文件
            $this->updateEnvFile([
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_name,
                'DB_USERNAME' => $request->db_username,
                'DB_PASSWORD' => $request->db_password,
            ]);

            // 清除配置缓存
            Artisan::call('config:clear');

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '数据库连接失败：' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 步骤3：管理员设置
     */
    private function step3()
    {
        return view('install.step3');
    }

    /**
     * 处理管理员设置
     */
    public function handleAdminConfig(Request $request)
    {
        $request->validate([
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // 记录开始日志
            \Log::info('开始创建管理员账户', $request->only(['admin_name', 'admin_email']));
            
            // 执行数据库迁移
            \Log::info('开始执行数据库迁移');
            Artisan::call('migrate', ['--force' => true]);
            \Log::info('数据库迁移完成');

            // 创建管理员账户
            \Log::info('开始创建管理员用户');
            $adminUser = AdminUser::create([
                'name' => $request->admin_name,
                'username' => 'admin',
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'status' => 'active',
                'is_super_admin' => true,
            ]);
            \Log::info('管理员用户创建成功', ['user_id' => $adminUser->id]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('创建管理员账户失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '管理员创建失败：' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 步骤4：站点配置
     */
    private function step4()
    {
        return view('install.step4');
    }

    /**
     * 处理站点配置
     */
    public function handleSiteConfig(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
        ]);

        try {
            // 更新.env文件
            $this->updateEnvFile([
                'APP_NAME' => '"' . $request->app_name . '"',
                'APP_URL' => $request->app_url,
                'APP_TIMEZONE' => $request->timezone,
            ]);

            // 生成应用密钥
            Artisan::call('key:generate', ['--force' => true]);

            // 清除缓存
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '站点配置失败：' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 步骤5：安装完成
     */
    private function step5()
    {
        // 创建安装标记文件
        $this->createInstallLock();

        // 获取安装数据用于显示摘要
        $adminUser = \App\Models\AdminUser::first();
        
        $installData = [
            'site_name' => env('APP_NAME', 'Gei5CMS'),
            'admin_username' => $adminUser ? $adminUser->username : 'admin',
            'admin_email' => $adminUser ? $adminUser->email : '未设置',
            'site_url' => env('APP_URL', request()->getSchemeAndHttpHost()),
        ];

        return view('install.step5', [
            'admin_url' => url('/admin'),
            'site_url' => url('/'),
            'installData' => $installData,
        ]);
    }

    /**
     * 检查系统要求
     */
    private function checkRequirements()
    {
        $requirements = [
            'php_version' => [
                'name' => 'PHP版本 >= 8.2',
                'status' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'extensions' => []
        ];

        $requiredExtensions = [
            'openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'
        ];

        foreach ($requiredExtensions as $ext) {
            $requirements['extensions'][$ext] = [
                'name' => $ext,
                'status' => extension_loaded($ext),
            ];
        }

        $allPassed = $requirements['php_version']['status'] && 
                    !in_array(false, array_column($requirements['extensions'], 'status'));

        return [
            'success' => $allPassed,
            'requirements' => $requirements
        ];
    }

    /**
     * 检查文件权限
     */
    private function checkPermissions()
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            '.env' => base_path('.env'),
        ];

        $permissions = [];
        $allWritable = true;

        foreach ($paths as $name => $path) {
            $writable = is_writable($path);
            $permissions[$name] = [
                'name' => $name,
                'path' => $path,
                'status' => $writable,
            ];
            
            if (!$writable) {
                $allWritable = false;
            }
        }

        return [
            'success' => $allWritable,
            'permissions' => $permissions
        ];
    }

    /**
     * 测试数据库连接
     */
    private function testDatabaseConnection($host, $port, $database, $username, $password)
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);
    }

    /**
     * 更新.env文件
     */
    private function updateEnvFile(array $data)
    {
        $envPath = base_path('.env');
        
        // 确保.env文件存在
        if (!File::exists($envPath)) {
            $this->createMinimalEnvFile();
        }
        
        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            // 如果值包含空格或特殊字符，加引号
            if (is_string($value) && (str_contains($value, ' ') || str_contains($value, '#'))) {
                $value = '"' . str_replace('"', '\"', $value) . '"';
            }
            
            if (str_contains($envContent, $key . '=')) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
        
        // 清除配置缓存以应用新配置
        if (function_exists('artisan')) {
            try {
                Artisan::call('config:clear');
            } catch (\Exception $e) {
                // 忽略清除缓存错误
            }
        }
    }

    /**
     * 检查是否已安装
     */
    private function isInstalled()
    {
        return File::exists(base_path('storage/installed.lock'));
    }

    /**
     * 创建安装锁定文件
     */
    private function createInstallLock()
    {
        File::put(base_path('storage/installed.lock'), json_encode([
            'installed_at' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ]));
    }
}