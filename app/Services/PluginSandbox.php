<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PluginSandbox
{
    protected array $allowedFunctions = [
        // 基本PHP函数
        'strlen', 'substr', 'strpos', 'str_replace', 'trim', 'ltrim', 'rtrim',
        'strtolower', 'strtoupper', 'ucfirst', 'ucwords', 'htmlspecialchars',
        'json_encode', 'json_decode', 'serialize', 'unserialize',
        'array_merge', 'array_keys', 'array_values', 'array_filter', 'array_map',
        'count', 'sizeof', 'empty', 'isset', 'is_array', 'is_string', 'is_numeric',
        'date', 'time', 'strtotime', 'microtime',
        'md5', 'sha1', 'hash', 'password_hash', 'password_verify',
        
        // Laravel helper functions
        'config', 'env', 'request', 'response', 'session', 'cookie',
        'route', 'url', 'asset', 'storage_path', 'public_path',
        'collect', 'data_get', 'data_set', 'head', 'last',
        'trans', '__', 'trans_choice',
    ];

    protected array $blockedFunctions = [
        // 系统函数
        'exec', 'system', 'shell_exec', 'passthru', 'proc_open', 'popen',
        'eval', 'assert', 'create_function', 'call_user_func', 'call_user_func_array',
        
        // 文件系统函数
        'file_get_contents', 'file_put_contents', 'fopen', 'fwrite', 'fread',
        'unlink', 'rmdir', 'mkdir', 'chmod', 'chown', 'rename',
        'glob', 'scandir', 'opendir', 'readdir',
        
        // 网络函数
        'curl_exec', 'curl_init', 'fsockopen', 'socket_create',
        'gethostbyname', 'gethostbyaddr',
        
        // 危险函数
        'phpinfo', 'get_current_user', 'getmypid', 'getmyuid', 'getmygid',
        'php_uname', 'getenv', 'putenv',
        
        // 反射相关
        'get_defined_functions', 'get_defined_constants', 'get_defined_vars',
        'function_exists', 'class_exists', 'method_exists',
    ];

    protected array $allowedClasses = [
        // Laravel核心类
        'Illuminate\Http\Request',
        'Illuminate\Http\Response',
        'Illuminate\Support\Collection',
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Cache',
        'Illuminate\Support\Facades\Log',
        'Illuminate\Support\Facades\Event',
        
        // 框架模型
        'App\Models\Setting',
        'App\Models\Hook',
        'App\Models\PluginData',
        
        // 标准PHP类
        'DateTime', 'DateTimeImmutable', 'DateInterval',
        'Exception', 'InvalidArgumentException', 'RuntimeException',
        'stdClass', 'ArrayObject', 'SplObjectStorage',
    ];

    protected array $blockedClasses = [
        // 反射类
        'ReflectionClass', 'ReflectionMethod', 'ReflectionFunction',
        'ReflectionProperty', 'ReflectionParameter',
        
        // 文件系统类
        'SplFileObject', 'SplFileInfo', 'DirectoryIterator',
        'RecursiveDirectoryIterator', 'FilesystemIterator',
        
        // PDO相关
        'PDO', 'PDOStatement',
        
        // 进程相关
        'Process', 'Symfony\Component\Process\Process',
    ];

    public function executeInSandbox(callable $callback, array $context = []): mixed
    {
        $originalErrorHandler = set_error_handler([$this, 'handleError']);
        $originalExceptionHandler = set_exception_handler([$this, 'handleException']);
        
        // 设置执行时间限制
        $originalTimeLimit = ini_get('max_execution_time');
        ini_set('max_execution_time', 30);
        
        // 设置内存限制
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '128M');

        try {
            // 创建沙箱环境
            $sandbox = new PluginSandboxEnvironment($this->allowedFunctions, $this->allowedClasses);
            
            // 在沙箱中执行代码
            return $sandbox->execute($callback, $context);
            
        } catch (\Throwable $e) {
            Log::error('Plugin sandbox execution failed: ' . $e->getMessage());
            throw $e;
        } finally {
            // 恢复设置
            ini_set('max_execution_time', $originalTimeLimit);
            ini_set('memory_limit', $originalMemoryLimit);
            
            if ($originalErrorHandler !== null) {
                set_error_handler($originalErrorHandler);
            } else {
                restore_error_handler();
            }
            
            if ($originalExceptionHandler !== null) {
                set_exception_handler($originalExceptionHandler);
            } else {
                restore_exception_handler();
            }
        }
    }

    public function validatePluginCode(string $code): array
    {
        $violations = [];
        
        // 检查禁用函数
        foreach ($this->blockedFunctions as $func) {
            if (strpos($code, $func) !== false) {
                $violations[] = "使用了禁止的函数: {$func}";
            }
        }
        
        // 检查禁用类
        foreach ($this->blockedClasses as $class) {
            if (strpos($code, $class) !== false) {
                $violations[] = "使用了禁止的类: {$class}";
            }
        }
        
        // 检查危险模式
        $dangerousPatterns = [
            '/\$\$/' => '变量变量',
            '/`[^`]*`/' => '命令执行',
            '/__halt_compiler\(\)/' => '编译器停止',
            '/goto\s+\w+/' => 'goto语句',
            '/\bextract\s*\(/' => 'extract函数',
            '/\bcompact\s*\(/' => 'compact函数',
            '/\$_GET\[|\$_POST\[|\$_REQUEST\[|\$_COOKIE\[/' => '直接访问超全局变量',
        ];
        
        foreach ($dangerousPatterns as $pattern => $description) {
            if (preg_match($pattern, $code)) {
                $violations[] = "检测到危险模式: {$description}";
            }
        }
        
        return $violations;
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        Log::warning("Plugin sandbox error: {$message} in {$file}:{$line}");
        return true;
    }

    public function handleException(\Throwable $exception): void
    {
        Log::error("Plugin sandbox exception: " . $exception->getMessage());
    }

    public function isAllowedFunction(string $function): bool
    {
        return in_array($function, $this->allowedFunctions) && 
               !in_array($function, $this->blockedFunctions);
    }

    public function isAllowedClass(string $class): bool
    {
        return in_array($class, $this->allowedClasses) && 
               !in_array($class, $this->blockedClasses);
    }

    public function addAllowedFunction(string $function): void
    {
        if (!in_array($function, $this->allowedFunctions)) {
            $this->allowedFunctions[] = $function;
        }
    }

    public function addAllowedClass(string $class): void
    {
        if (!in_array($class, $this->allowedClasses)) {
            $this->allowedClasses[] = $class;
        }
    }
}

class PluginSandboxEnvironment
{
    protected array $allowedFunctions;
    protected array $allowedClasses;
    protected array $context;

    public function __construct(array $allowedFunctions, array $allowedClasses)
    {
        $this->allowedFunctions = $allowedFunctions;
        $this->allowedClasses = $allowedClasses;
    }

    public function execute(callable $callback, array $context = []): mixed
    {
        $this->context = $context;
        
        // 在受限环境中执行回调
        return call_user_func($callback, $this->context);
    }

    public function __call(string $method, array $args)
    {
        if (!$this->isAllowedFunction($method)) {
            throw new \RuntimeException("Function '{$method}' is not allowed in plugin sandbox");
        }
        
        return call_user_func_array($method, $args);
    }

    protected function isAllowedFunction(string $function): bool
    {
        return in_array($function, $this->allowedFunctions);
    }
}