# Gei5CMS 现代化钩子系统技术文档

## 概述

Gei5CMS 采用全新设计的现代化钩子系统，**完全不兼容 WordPress**，基于 Laravel 原生特性构建，提供更好的性能、类型安全和开发体验。

## 核心架构

### 1. 系统组成

```
HookManager (核心管理器)
├── HookServiceProvider (服务提供者)
├── Hook (Facade门面)
├── Blade指令 (@hook, @filter, @hookAsync等)
└── AdminMenuService (集成示例)
```

### 2. 核心类

- **HookManager**: 钩子系统核心管理类
- **HookServiceProvider**: 系统启动和Blade指令注册
- **Hook Facade**: 提供静态访问接口
- **Blade指令**: 模板层钩子调用支持

## 主要特性

### 1. Laravel 原生集成
- 基于 Laravel 服务容器
- 使用依赖注入和门面模式
- 集成 Queue 和 Event 系统

### 2. 现代化 API 设计
```php
// 注册钩子
Hook::registerHook('hook.name', $callback, $priority, $pluginSlug, $hookType);

// 执行动作钩子
Hook::doAction('hook.name', $arg1, $arg2);

// 执行过滤器钩子
$result = Hook::applyFilters('filter.name', $value, $arg1, $arg2);

// 异步执行钩子
Hook::executeHookAsync('async.hook', $data);
```

### 3. 性能优化
- 内存中缓存已注册钩子
- 支持异步钩子执行
- 执行时间统计和慢钩子警告
- 按优先级自动排序

### 4. 类型安全
- 完整的 PHPDoc 类型注释
- 钩子标签格式验证
- 回调函数序列化/反序列化

## Blade 指令系统

### 基础指令

```blade
{{-- 执行动作钩子 --}}
@hook('admin.sidebar.app_menu')

{{-- 执行过滤器钩子并输出 --}}
@filter('content.title', $title, $context)

{{-- 异步执行钩子 --}}
@hookAsync('notification.send', $data)

{{-- 条件钩子检查 --}}
@hasHook('custom.feature')
    <div>功能可用</div>
@endhasHook

{{-- 捕获钩子输出 --}}
@hookOutput('widget.sidebar')

{{-- 显示钩子数量 --}}
钩子数量: @hookCount('admin.menu')

{{-- 显示系统统计 --}}
@hookStats
```

### 高级用法

```blade
{{-- 在布局模板中使用 --}}
<div class="sidebar">
    @hook('admin.sidebar.before')
    
    <nav class="nav">
        @hook('admin.sidebar.app_menu')
    </nav>
    
    @hook('admin.sidebar.after')
</div>
```

## 与 AdminMenuService 集成

### 钩子注册
```php
// 在 HookServiceProvider 中自动注册
$hookManager->registerHook('admin.sidebar.app_menu', function () {
    \App\Services\AdminMenuService::renderSidebarMenus();
}, 10, 'core');
```

### 菜单动态注册
```php
// 插件可以通过钩子注册菜单
Hook::registerHook('admin.menu.init', function ($menuService) {
    AdminMenuService::register('my_plugin_menu', [
        'key' => 'my-plugin',
        'label' => '我的插件',
        'route' => 'admin.my-plugin.index',
        'icon' => 'ti ti-plugin',
        'priority' => 30,
        'children' => [
            [
                'key' => 'settings',
                'label' => '设置',
                'route' => 'admin.my-plugin.settings',
                'icon' => 'ti ti-settings'
            ]
        ]
    ]);
});
```

### 菜单过滤
```php
// 通过过滤器修改菜单
Hook::registerHook('admin.menu.filter', function ($menus) {
    // 修改或添加菜单项
    $menus['custom_section'] = [
        'key' => 'custom-section',
        'label' => '自定义区域',
        'route' => 'admin.custom.index',
        'position' => 'bottom'
    ];
    return $menus;
}, 10, 'my_plugin', 'filter');
```

## 钩子类型

### 1. 动作钩子 (Action)
```php
// 注册动作钩子
Hook::registerHook('user.created', function ($user) {
    // 处理用户创建后的逻辑
    Log::info("User created: {$user->email}");
}, 10, 'user_plugin');

// 触发动作钩子
Hook::doAction('user.created', $user);
```

### 2. 过滤器钩子 (Filter)
```php
// 注册过滤器钩子
Hook::registerHook('content.title', function ($title, $post) {
    return "[置顶] " . $title;
}, 10, 'content_plugin', 'filter');

// 应用过滤器
$title = Hook::applyFilters('content.title', $post->title, $post);
```

### 3. 异步钩子 (Async)
```php
// 注册异步钩子
Hook::registerHook('email.send', function ($user, $template) {
    // 异步发送邮件
    Mail::to($user)->queue(new NotificationMail($template));
}, 10, 'email_plugin', 'async');

// 触发异步钩子
Hook::executeHookAsync('email.send', $user, $template);
```

## 标准钩子列表

### 系统级钩子
- `system.boot` - 系统启动时触发
- `system.ready` - 系统准备完毕
- `system.error` - 系统错误时触发

### 用户钩子
- `user.login.before` - 用户登录前
- `user.login.after` - 用户登录后
- `user.logout.after` - 用户退出后

### 管理后台钩子
- `admin.menu.init` - 菜单系统初始化
- `admin.menu.filter` - 菜单过滤器
- `admin.sidebar.before` - 侧边栏渲染前
- `admin.sidebar.app_menu` - 应用菜单渲染
- `admin.sidebar.after` - 侧边栏渲染后
- `admin.sidebar.menu.before` - 菜单项渲染前
- `admin.sidebar.menu.after` - 菜单项渲染后

### 内容钩子
- `content.created` - 内容创建后
- `content.updated` - 内容更新后
- `content.deleted` - 内容删除后
- `content.title` - 标题过滤器
- `content.body` - 内容过滤器

### 插件钩子
- `plugin.activated` - 插件激活后
- `plugin.deactivated` - 插件停用后
- `plugin.installed` - 插件安装后
- `plugin.uninstalled` - 插件卸载后

### 主题钩子
- `theme.activated` - 主题激活后
- `theme.deactivated` - 主题停用后
- `theme.customizer.init` - 主题自定义器初始化

## 性能监控

### 统计信息
```php
$stats = Hook::getHookStatistics();
/*
返回结构:
[
    'total_hooks' => 25,
    'active_hooks' => 20,
    'tags' => ['admin.menu.init' => 3, ...],
    'plugins' => ['my_plugin' => 5, ...],
    'execution_stats' => [...]
]
*/
```

### 慢钩子监控
系统自动记录执行时间超过 1 秒的钩子，并写入日志：
```
[WARNING] Slow hook execution: admin.heavy.process took 1.25s
```

## 最佳实践

### 1. 钩子命名规范
- 使用小写字母和下划线
- 采用层级结构：`模块.操作.时机`
- 示例：`user.login.after`, `content.save.before`

### 2. 优先级设置
- 核心系统：1-10
- 主题：11-20  
- 插件：21-50
- 用户自定义：51-100

### 3. 错误处理
```php
Hook::registerHook('risky.operation', function () {
    try {
        // 可能出错的操作
    } catch (Exception $e) {
        Log::error("Hook failed: " . $e->getMessage());
        // 不要抛出异常，避免影响其他钩子
    }
});
```

### 4. 异步钩子使用场景
- 发送邮件通知
- 生成缩略图
- 数据统计计算
- 第三方API调用

## 插件开发示例

### 插件服务提供者
```php
// plugins/MyPlugin/src/Providers/MyPluginServiceProvider.php
class MyPluginServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 注册菜单
        Hook::registerHook('admin.menu.init', [$this, 'registerMenus'], 30, 'my_plugin');
        
        // 注册内容过滤器
        Hook::registerHook('content.title', [$this, 'filterTitle'], 20, 'my_plugin', 'filter');
    }
    
    public function registerMenus($menuService)
    {
        AdminMenuService::register('my_plugin_menu', [
            'key' => 'my-plugin',
            'label' => '我的插件',
            'route' => 'admin.my-plugin.index',
            'icon' => 'ti ti-plugin'
        ]);
    }
    
    public function filterTitle($title, $context = null)
    {
        if ($context && $context->is_featured) {
            return "⭐ " . $title;
        }
        return $title;
    }
}
```

## 与传统 WordPress 钩子对比

| 特性 | WordPress 钩子 | Gei5CMS 现代化钩子 |
|------|-----------------|-------------------|
| 类型安全 | ❌ | ✅ 完整 PHPDoc |
| 性能 | ⚠️ 一般 | ✅ 优化缓存 |
| 异步支持 | ❌ | ✅ 原生支持 |
| 依赖注入 | ❌ | ✅ Laravel DI |
| 错误处理 | ⚠️ 基础 | ✅ 完善日志 |
| 调试工具 | ⚠️ 有限 | ✅ 统计监控 |
| API 设计 | ⚠️ 传统 | ✅ 现代化 |

## 未来扩展计划

1. **可视化钩子调试器** - 开发后台管理界面
2. **钩子性能分析器** - 详细性能报告
3. **钩子依赖管理** - 钩子间依赖关系
4. **GraphQL 钩子集成** - 支持 GraphQL 查询
5. **微服务钩子** - 跨服务钩子调用

---

**文档版本**: 1.0  
**更新日期**: 2025年9月5日  
**维护者**: Gei5CMS 开发团队