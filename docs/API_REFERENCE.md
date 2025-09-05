# Gei5CMS API 和接口参考

## 📋 概述

本文档提供 Gei5CMS 框架的完整API和接口清单，包括钩子系统、辅助函数、服务接口等，供主题和插件开发者参考使用。

## 🎣 现代化钩子系统 API

### Hook Facade 接口

#### 注册钩子
```php
Hook::registerHook(
    string $tag,           // 钩子标签
    callable $callback,    // 回调函数
    int $priority = 10,    // 优先级（数字越小优先级越高）
    string $pluginSlug = null,  // 插件标识
    string $hookType = 'action'  // 钩子类型: 'action'|'filter'|'async'
): bool
```

**示例**:
```php
// 动作钩子
Hook::registerHook('user.created', function($user) {
    Log::info("User created: {$user->email}");
}, 10, 'my_plugin');

// 过滤器钩子
Hook::registerHook('post.title', function($title, $post) {
    return "[置顶] " . $title;
}, 20, 'content_plugin', 'filter');

// 异步钩子
Hook::registerHook('email.send', function($user, $template) {
    Mail::to($user)->queue(new NotificationMail($template));
}, 10, 'email_plugin', 'async');
```

#### 执行钩子
```php
// 执行动作钩子
Hook::doAction(string $tag, ...$args): array

// 应用过滤器钩子  
Hook::applyFilters(string $tag, mixed $value, ...$args): mixed

// 异步执行钩子
Hook::executeHookAsync(string $tag, ...$args): void
```

**示例**:
```php
// 触发用户创建钩子
Hook::doAction('user.created', $user);

// 应用标题过滤器
$filteredTitle = Hook::applyFilters('post.title', $post->title, $post);

// 异步发送邮件
Hook::executeHookAsync('email.send', $user, $emailTemplate);
```

#### 钩子管理
```php
// 移除钩子
Hook::removeHook(string $tag, string $source = null): bool

// 检查钩子是否存在
Hook::hasHook(string $tag): bool

// 获取已注册钩子
Hook::getRegisteredHooks(string $tag = null): array

// 获取钩子统计
Hook::getHookStatistics(): array
```

### Blade 钩子指令

#### @hook 指令
```blade
{{-- 执行动作钩子 --}}
@hook('admin.sidebar.app_menu')

{{-- 带参数执行钩子 --}}
@hook('content.render', $post, $context)
```

#### @filter 指令
```blade
{{-- 应用过滤器并输出 --}}
@filter('post.excerpt', $post->content, 150)

{{-- 输出过滤后的标题 --}}
@filter('post.title', $post->title, $post)
```

#### @hasHook 条件指令
```blade
@hasHook('custom.sidebar')
    <div class="custom-sidebar">
        @hook('custom.sidebar')
    </div>
@endhasHook
```

#### 其他钩子指令
```blade
{{-- 异步钩子 --}}
@hookAsync('analytics.track', $event)

{{-- 捕获钩子输出 --}}
@hookOutput('widget.sidebar')

{{-- 显示钩子数量 --}}
当前钩子数量: @hookCount('admin.menu')

{{-- 系统钩子统计 --}}
@hookStats
```

## 🍎 AdminMenuService API

### 菜单注册
```php
AdminMenuService::register(string $key, array $menu): void
```

**菜单结构**:
```php
[
    'key' => 'unique-menu-key',        // 唯一标识
    'label' => '菜单显示名称',          // 显示名称
    'icon' => 'ti ti-icon-name',       // 图标类名
    'route' => 'admin.route.name',     // Laravel 路由名
    'priority' => 50,                  // 优先级
    'position' => 'middle',            // 位置: top|middle|bottom
    'permission' => 'permission.name', // 权限检查
    'children' => [                    // 子菜单（可选）
        [
            'key' => 'sub-menu',
            'label' => '子菜单',
            'route' => 'admin.sub.route',
            'icon' => 'ti ti-circle'
        ]
    ]
]
```

### 菜单获取
```php
// 获取所有菜单
AdminMenuService::getMenus(): array

// 获取指定位置菜单
AdminMenuService::getMenusByPosition(string $position): array

// 渲染侧边栏菜单（用于钩子调用）
AdminMenuService::renderSidebarMenus(): void
```

## 🔧 全局辅助函数

### 钩子相关函数
```php
// 执行动作钩子（兼容函数）
do_action(string $hookName, ...$args): void

// 应用过滤器钩子（兼容函数）
apply_filters(string $hookName, mixed $value, ...$args): mixed

// 注册动作钩子（兼容函数）
add_action(string $hookName, callable $callback, int $priority = 10, string $source = 'custom'): bool

// 注册过滤器钩子（兼容函数）
add_filter(string $hookName, callable $callback, int $priority = 10, string $source = 'custom'): bool

// 移除钩子（兼容函数）
remove_action(string $hookName, string $source = 'custom'): bool
remove_filter(string $hookName, string $source = 'custom'): bool

// 检查钩子（兼容函数）
has_action(string $hookName): bool
has_filter(string $hookName): bool
```

### 路径相关函数
```php
// 获取插件路径
get_plugin_path(string $pluginName): string

// 获取主题路径  
get_theme_path(string $themeName): string

// 获取插件URL
get_plugin_url(string $pluginName, string $path = ''): string

// 获取主题URL
get_theme_url(string $themeName, string $path = ''): string
```

### 设置相关函数
```php
// 获取系统设置
get_option(string $key, mixed $default = null): mixed

// 更新系统设置
update_option(string $key, mixed $value, string $group = 'general'): bool
```

### 状态检查函数
```php
// 检查插件是否激活
is_plugin_active(string $pluginName): bool

// 检查主题是否激活
is_theme_active(string $themeName): bool
```

## 🎨 标准钩子清单

### 系统级钩子

#### 应用生命周期
```php
'system.boot'        // 系统启动
'system.ready'       // 系统就绪
'system.error'       // 系统错误
```

#### 用户管理
```php
'user.login.before'  // 用户登录前
'user.login.after'   // 用户登录后
'user.logout.after'  // 用户退出后
```

### 管理后台钩子

#### 菜单系统
```php
'admin.menu.init'              // 菜单初始化
'admin.menu.filter'            // 菜单过滤器（filter类型）
'admin.sidebar.before'         // 侧边栏渲染前
'admin.sidebar.app_menu'       // 应用菜单渲染
'admin.sidebar.after'          // 侧边栏渲染后
'admin.sidebar.menu.before'    // 单个菜单渲染前
'admin.sidebar.menu.after'     // 单个菜单渲染后
```

#### 仪表盘钩子
```php
'admin.dashboard.stats'        // 仪表盘统计数据（filter）
'admin.dashboard.widgets'      // 仪表盘小部件（filter）
'admin.dashboard.actions'      // 快捷操作（filter）
```

### 插件系统钩子

#### 生命周期
```php
'plugin.activated'      // 插件激活后
'plugin.deactivated'    // 插件停用后
'plugin.installed'      // 插件安装后
'plugin.uninstalled'    // 插件卸载后
'plugin.updated'        // 插件更新后
```

#### 数据操作
```php
'plugin.data.save'      // 插件数据保存
'plugin.data.load'      // 插件数据加载
'plugin.config.update'  // 插件配置更新
```

### 主题系统钩子

#### 生命周期
```php
'theme.activated'       // 主题激活后
'theme.deactivated'     // 主题停用后
'theme.installed'       // 主题安装后
'theme.uninstalled'     // 主题卸载后
```

#### 定制化
```php
'theme.customizer.init'     // 主题定制器初始化
'theme.assets.load'         // 主题资源加载
'theme.template.render'     // 模板渲染
```

## 🔌 插件开发接口

### 插件配置文件 (plugin.json)
```json
{
    "name": "插件名称",
    "slug": "plugin-slug",
    "version": "1.0.0",
    "description": "插件描述",
    "author": {
        "name": "作者名称",
        "email": "author@example.com",
        "url": "https://example.com"
    },
    "requires": {
        "gei5cms": ">=1.0.0",
        "php": ">=8.2"
    },
    "main": "src/Providers/PluginServiceProvider.php",
    "autoload": {
        "psr-4": {
            "MyPlugin\\": "src/"
        }
    }
}
```

### 插件服务提供者基类
```php
abstract class BasePluginServiceProvider extends ServiceProvider
{
    protected string $pluginSlug;
    
    public function boot(): void
    {
        $this->registerHooks();
        $this->registerRoutes(); 
        $this->registerViews();
    }
    
    abstract protected function registerHooks(): void;
    
    protected function registerRoutes(): void
    {
        // 注册路由
    }
    
    protected function registerViews(): void
    {
        // 注册视图
    }
}
```

## 🎨 主题开发接口

### 主题配置文件 (theme.json)
```json
{
    "name": "主题名称",
    "slug": "theme-slug",
    "version": "1.0.0", 
    "description": "主题描述",
    "author": {
        "name": "作者名称",
        "email": "author@example.com"
    },
    "screenshots": [
        "screenshot.png"
    ],
    "tags": ["blog", "responsive", "modern"],
    "requires": {
        "gei5cms": ">=1.0.0"
    }
}
```

### 主题服务提供者基类
```php
abstract class BaseThemeServiceProvider extends ServiceProvider
{
    protected string $themeSlug;
    
    public function boot(): void
    {
        $this->registerHooks();
        $this->registerMenus();
        $this->registerRoutes();
        $this->registerViews();
    }
    
    abstract protected function registerMenus(): void;
    abstract protected function registerHooks(): void;
}
```

## 📊 数据模型接口

### 核心模型

#### AdminUser 模型
```php
// 管理员用户相关操作
AdminUser::create($data)
AdminUser::findByUsername($username)
AdminUser::findByEmail($email)
```

#### Plugin 模型
```php
// 插件状态管理
Plugin::activate($slug)
Plugin::deactivate($slug) 
Plugin::isActive($slug)
Plugin::getByStatus($status)
```

#### Theme 模型
```php
// 主题状态管理
Theme::activate($slug)
Theme::deactivate($slug)
Theme::isActive($slug)
Theme::getActive()
```

#### Setting 模型
```php
// 系统设置管理
Setting::get($key, $default = null)
Setting::set($key, $value, $group = 'general')
Setting::getByGroup($group)
```

## 🔍 调试和开发工具

### 钩子调试
```php
// 获取钩子统计信息
$stats = Hook::getHookStatistics();

// 检查特定钩子
if (Hook::hasHook('my.custom.hook')) {
    $hooks = Hook::getRegisteredHooks('my.custom.hook');
}
```

### 性能监控
钩子系统自动监控执行时间超过1秒的钩子，并记录到日志中：
```
[WARNING] Slow hook execution: heavy.process took 1.25s
```

---

**文档版本**: 1.0  
**最后更新**: 2025年9月5日  
**适用版本**: Gei5CMS v1.0.0