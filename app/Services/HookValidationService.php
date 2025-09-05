<?php

namespace App\Services;

use App\Models\Hook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HookValidationService
{
    protected HookRegistry $hookRegistry;
    protected array $validationRules = [];
    protected array $validationErrors = [];

    public function __construct()
    {
        $this->hookRegistry = new HookRegistry();
        $this->setupValidationRules();
    }

    public function validateHookRegistration(
        string $tag,
        callable $callback,
        int $priority = 10,
        string $pluginSlug = null,
        string $hookType = 'action'
    ): array {
        $errors = [];

        // 验证钩子标签
        $tagValidation = $this->validateHookTag($tag);
        if (!$tagValidation['valid']) {
            $errors['tag'] = $tagValidation['errors'];
        }

        // 验证回调函数
        $callbackValidation = $this->validateCallback($callback);
        if (!$callbackValidation['valid']) {
            $errors['callback'] = $callbackValidation['errors'];
        }

        // 验证优先级
        $priorityValidation = $this->validatePriority($priority);
        if (!$priorityValidation['valid']) {
            $errors['priority'] = $priorityValidation['errors'];
        }

        // 验证插件slug
        if ($pluginSlug && !$this->validatePluginSlug($pluginSlug)) {
            $errors['plugin_slug'] = ['Invalid plugin slug format'];
        }

        // 验证钩子类型
        if (!$this->validateHookType($hookType)) {
            $errors['hook_type'] = ['Invalid hook type'];
        }

        // 验证钩子是否已存在
        $duplicateValidation = $this->checkDuplicateHook($tag, $callback, $pluginSlug);
        if (!$duplicateValidation['valid']) {
            $errors['duplicate'] = $duplicateValidation['errors'];
        }

        // 验证插件权限
        if ($pluginSlug && !$this->validatePluginPermissions($pluginSlug, $tag)) {
            $errors['permissions'] = ['Plugin does not have permission to register this hook'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function validateHookExecution(string $tag, array $args): array
    {
        $errors = [];

        // 检查钩子是否存在
        if (!$this->hookRegistry->isValidHook($tag) && !$this->isCustomHook($tag)) {
            $errors['tag'] = ['Hook tag not found in registry'];
        }

        // 验证参数
        $argsValidation = $this->validateHookArguments($tag, $args);
        if (!$argsValidation['valid']) {
            $errors['arguments'] = $argsValidation['errors'];
        }

        // 检查循环依赖
        $circularCheck = $this->checkCircularDependency($tag);
        if (!$circularCheck['valid']) {
            $errors['circular'] = $circularCheck['errors'];
        }

        // 检查执行权限
        if (!$this->checkExecutionPermissions($tag)) {
            $errors['execution'] = ['No permission to execute this hook'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function validateCallback(callable $callback): array
    {
        $errors = [];

        try {
            // 检查回调是否可调用
            if (!is_callable($callback)) {
                $errors[] = 'Callback is not callable';
                return ['valid' => false, 'errors' => $errors];
            }

            // 检查回调类型
            if (is_string($callback)) {
                if (!function_exists($callback)) {
                    $errors[] = "Function '{$callback}' does not exist";
                }
            } elseif (is_array($callback)) {
                if (count($callback) !== 2) {
                    $errors[] = 'Array callback must have exactly 2 elements';
                } else {
                    $class = $callback[0];
                    $method = $callback[1];

                    if (is_string($class)) {
                        if (!class_exists($class)) {
                            $errors[] = "Class '{$class}' does not exist";
                        } elseif (!method_exists($class, $method)) {
                            $errors[] = "Method '{$method}' does not exist in class '{$class}'";
                        }
                    } elseif (is_object($class)) {
                        if (!method_exists($class, $method)) {
                            $errors[] = "Method '{$method}' does not exist in object";
                        }
                    }
                }
            } elseif ($callback instanceof \Closure) {
                // 闭包有效，但警告序列化问题
                $errors[] = 'Closures cannot be serialized for database storage';
            }

            // 检查回调安全性
            $securityCheck = $this->checkCallbackSecurity($callback);
            if (!$securityCheck['valid']) {
                $errors = array_merge($errors, $securityCheck['errors']);
            }

        } catch (\Exception $e) {
            $errors[] = 'Error validating callback: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function validateHookTag(string $tag): array
    {
        $errors = [];

        // 基本格式验证
        if (!preg_match('/^[a-z_][a-z0-9_\.]*$/', $tag)) {
            $errors[] = 'Hook tag must start with lowercase letter or underscore and contain only lowercase letters, numbers, dots, and underscores';
        }

        // 长度验证
        if (strlen($tag) < 3) {
            $errors[] = 'Hook tag must be at least 3 characters long';
        }

        if (strlen($tag) > 64) {
            $errors[] = 'Hook tag must not exceed 64 characters';
        }

        // 保留字检查
        $reservedTags = ['system', 'core', 'internal', 'admin'];
        $tagParts = explode('.', $tag);
        if (in_array($tagParts[0], $reservedTags)) {
            $errors[] = 'Hook tag cannot start with reserved words: ' . implode(', ', $reservedTags);
        }

        // 命名约定检查
        if (substr_count($tag, '.') > 4) {
            $errors[] = 'Hook tag cannot have more than 4 levels (dots)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function sanitizeHookData(array $data): array
    {
        $sanitized = [];

        // 清理钩子标签
        if (isset($data['tag'])) {
            $sanitized['tag'] = strtolower(trim($data['tag']));
            $sanitized['tag'] = preg_replace('/[^a-z0-9_\.]/', '', $sanitized['tag']);
        }

        // 清理优先级
        if (isset($data['priority'])) {
            $sanitized['priority'] = max(1, min(100, (int) $data['priority']));
        }

        // 清理插件slug
        if (isset($data['plugin_slug'])) {
            $sanitized['plugin_slug'] = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim($data['plugin_slug'])));
        }

        // 清理钩子类型
        if (isset($data['hook_type'])) {
            $validTypes = ['action', 'filter', 'async'];
            $sanitized['hook_type'] = in_array($data['hook_type'], $validTypes) ? $data['hook_type'] : 'action';
        }

        return $sanitized;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function clearValidationErrors(): void
    {
        $this->validationErrors = [];
    }

    public function logValidationError(string $context, array $errors): void
    {
        $this->validationErrors[] = [
            'context' => $context,
            'errors' => $errors,
            'timestamp' => now(),
        ];

        Log::warning("Hook validation failed: {$context}", $errors);
    }

    protected function validatePriority(int $priority): array
    {
        $errors = [];

        if ($priority < 1 || $priority > 100) {
            $errors[] = 'Priority must be between 1 and 100';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    protected function validatePluginSlug(string $pluginSlug): bool
    {
        return preg_match('/^[a-z][a-z0-9_\-]{2,32}$/', $pluginSlug) === 1;
    }

    protected function validateHookType(string $hookType): bool
    {
        return in_array($hookType, ['action', 'filter', 'async']);
    }

    protected function checkDuplicateHook(string $tag, callable $callback, string $pluginSlug = null): array
    {
        $errors = [];

        // 检查数据库中是否已存在相同的钩子
        $existingHook = Hook::where('tag', $tag)
            ->where('plugin_slug', $pluginSlug)
            ->first();

        if ($existingHook) {
            $errors[] = 'Hook already registered by this plugin';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    protected function validatePluginPermissions(string $pluginSlug, string $hookTag): bool
    {
        // 检查插件是否有权限注册此钩子
        // 这里可以实现更复杂的权限检查逻辑
        
        $plugin = \App\Models\Plugin::where('slug', $pluginSlug)->first();
        if (!$plugin || $plugin->status !== 'active') {
            return false;
        }

        // 检查是否是系统级钩子（需要特殊权限）
        $systemHooks = ['system.', 'core.', 'database.', 'security.'];
        foreach ($systemHooks as $prefix) {
            if (strpos($hookTag, $prefix) === 0) {
                // 检查插件是否有系统级权限
                return false; // 默认禁止
            }
        }

        return true;
    }

    protected function validateHookArguments(string $tag, array $args): array
    {
        $errors = [];

        // 检查参数数量
        $expectedArgs = $this->getExpectedArguments($tag);
        if ($expectedArgs !== null && count($args) !== count($expectedArgs)) {
            $errors[] = "Expected " . count($expectedArgs) . " arguments, got " . count($args);
        }

        // 检查参数类型
        foreach ($expectedArgs ?? [] as $index => $expectedType) {
            if (isset($args[$index]) && !$this->validateArgumentType($args[$index], $expectedType)) {
                $errors[] = "Argument {$index} should be of type {$expectedType}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    protected function checkCircularDependency(string $tag): array
    {
        // 这里可以实现循环依赖检查逻辑
        return ['valid' => true, 'errors' => []];
    }

    protected function checkExecutionPermissions(string $tag): bool
    {
        // 检查当前用户是否有权限执行此钩子
        return true; // 简化实现
    }

    protected function isCustomHook(string $tag): bool
    {
        return Hook::where('tag', $tag)->exists();
    }

    protected function checkCallbackSecurity(callable $callback): array
    {
        $errors = [];

        // 检查危险函数
        $dangerousFunctions = [
            'exec', 'system', 'shell_exec', 'passthru', 'eval',
            'file_get_contents', 'file_put_contents', 'unlink'
        ];

        if (is_string($callback) && in_array($callback, $dangerousFunctions)) {
            $errors[] = "Dangerous function '{$callback}' is not allowed in hooks";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    protected function getExpectedArguments(string $tag): ?array
    {
        // 定义特定钩子的参数期望
        $argDefinitions = [
            'user.login.after' => ['object', 'object'], // $user, $request
            'content.create.after' => ['object'], // $content
            'payment.success' => ['object', 'object'], // $payment, $order
        ];

        return $argDefinitions[$tag] ?? null;
    }

    protected function validateArgumentType($value, string $expectedType): bool
    {
        switch ($expectedType) {
            case 'string':
                return is_string($value);
            case 'int':
            case 'integer':
                return is_int($value);
            case 'float':
                return is_float($value);
            case 'bool':
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            default:
                return true; // 未知类型，允许通过
        }
    }

    protected function setupValidationRules(): void
    {
        $this->validationRules = [
            'hook_registration' => [
                'tag' => 'required|string|min:3|max:64|regex:/^[a-z_][a-z0-9_\.]*$/',
                'priority' => 'integer|min:1|max:100',
                'plugin_slug' => 'nullable|string|min:3|max:32|regex:/^[a-z][a-z0-9_\-]*$/',
                'hook_type' => 'string|in:action,filter,async',
            ],
        ];
    }
}