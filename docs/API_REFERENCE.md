# Gei5CMS API 和接口参考

## 概述

本文档提供 Gei5CMS 多形态Web应用引擎的完整API和接口清单，包括钩子系统、辅助函数、服务接口等，供主题和插件开发者参考使用。

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

## 🔧 服务类接口

### ThemeUserService API

完整的主题用户管理服务，为主题提供强大的用户管理功能：

```php
$themeUserService = app(ThemeUserService::class);

// 角色管理
$role = $themeUserService->createRole([
    'role_slug' => 'premium_member',
    'role_name' => '高级会员', 
    'role_description' => '享受高级功能的会员用户',
    'permissions' => ['premium.access', 'premium.download'],
    'theme_slug' => 'ecommerce_theme',
    'priority' => 100,
]);

$roles = $themeUserService->getThemeRoles('ecommerce_theme');
$deleted = $themeUserService->deleteThemeRole('premium_member', 'ecommerce_theme');

// 用户角色分配
$success = $themeUserService->assignRoleToUser($user, 'premium_member', [
    'expires_at' => now()->addDays(30)
]);
$success = $themeUserService->removeRoleFromUser($user, 'premium_member');

// 用户元数据管理
$success = $themeUserService->setUserMeta($user, [
    'phone' => '13800138000',
    'vip_level' => 3,
    'preferences' => ['dark_mode' => true]
]);
$metaData = $themeUserService->getUserMeta($user, 'phone');
$allMeta = $themeUserService->getUserMeta($user);

// 权限检查（支持缓存）
$canPublish = $themeUserService->userCan($user, 'content.publish');

// 获取用户主题角色
$themeRoles = $themeUserService->getUserThemeRoles($user, 'blog_theme');

// 高级用户查询
$query = $themeUserService->getUserQuery([
    'roles' => ['premium_member', 'vip_user'],
    'meta' => ['vip_level' => 3, 'status' => 'active'],
    'verified' => true,
    'registered_after' => '2025-01-01',
    'search' => 'john@example.com'
]);

$users = $query->paginate(15);

// 批量用户操作
$results = $themeUserService->bulkUserAction([1, 2, 3, 4, 5], 'assign_role', [
    'role_slug' => 'premium_member',
    'expires_at' => now()->addDays(30)
]);

$results = $themeUserService->bulkUserAction([6, 7, 8], 'set_meta', [
    'meta_data' => ['newsletter' => true, 'vip_level' => 2]
]);

$results = $themeUserService->bulkUserAction([9, 10], 'verify_email');

// 用户统计数据
$stats = $themeUserService->getUserStats([
    'roles' => ['premium_member'],
    'verified' => true
]);
// 返回: ['total' => 150, 'verified' => 145, 'registered_today' => 5, ...]

// 邮箱验证管理
$success = $themeUserService->verifyUserEmail($user);
$success = $themeUserService->unverifyUserEmail($user);

// 主题钩子注册（自动处理主题切换）
$themeUserService->registerThemeHooks('my_theme');
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
'user.login.before'    // 用户登录前
'user.login.after'     // 用户登录后
'user.logout.after'    // 用户退出后
'user.created'         // 用户创建后
'user.updated'         // 用户更新后
'user.deleted'         // 用户删除后

// 用户角色钩子
'theme.user.role.creating'    // 角色创建前
'theme.user.role.created'     // 角色创建后
'theme.user.role.assigned'    // 角色分配后
'theme.user.role.removed'     // 角色移除后
'theme.user.role.deleting'    // 角色删除前
'theme.user.role.deleted'     // 角色删除后

// 用户元数据钩子
'theme.user.meta.updating'    // 元数据更新前
'theme.user.meta.updated'     // 元数据更新后

// 批量操作钩子
'theme.users.bulk_action.start'     // 批量操作开始
'theme.users.bulk_action.complete'  // 批量操作完成

// 邮箱验证钩子
'theme.user.email.verified'         // 邮箱验证后
'theme.user.email.unverified'       // 邮箱验证取消后
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

#### User 模型 (前台用户)
```php
// 基础用户操作
User::create($data)
User::findByEmail($email)

// 元数据操作
$user->setMeta($key, $value)
$user->getMeta($key, $default = null)
$user->getAllMeta()
$user->deleteMeta($key)
$user->syncMeta($data)

// 角色和权限
$user->assignRole($roleSlug, $options = [])
$user->removeRole($roleSlug)
$user->syncRoles($roleSlugs)
$user->hasRole($roleSlug)
$user->hasAnyRole($roleSlugs)
$user->hasAllRoles($roleSlugs)
$user->hasPermission($permission)
$user->getAllPermissions()

// 获取角色信息
$user->getRoleNames()
$user->getRoleSlugs()
$user->getHighestPriorityRole()
$user->getRolesInTheme($themeSlug)
$user->hasRoleInTheme($themeSlug)

// 查询作用域
User::verified()
User::unverified()
User::withRole($roleSlug)
User::withMeta($key, $value = null)
```

#### UserRole 模型
```php
// 角色管理
UserRole::create($data)
UserRole::active()
UserRole::byTheme($themeSlug)
UserRole::byPriority($direction = 'desc')

// 权限操作
$role->hasPermission($permission)
$role->givePermission($permission)
$role->revokePermission($permission)
$role->syncPermissions($permissions)

// 用户分配
$role->assignToUser($user, $options = [])
$role->removeFromUser($user)
$role->belongsToTheme($themeSlug)
$role->isExpiredForUser($user)
```

#### UserMeta 模型
```php
// 元数据查询
UserMeta::byKey($key)
UserMeta::byUser($userId)

// 值转换
$meta->formatted_value  // 自动类型转换
```

#### UserRoleAssignment 模型
```php
// 分配状态检查
$assignment->isExpired()
$assignment->isActive()

// 查询作用域
UserRoleAssignment::active()
UserRoleAssignment::expired()
UserRoleAssignment::forUser($userId)
UserRoleAssignment::forRole($roleId)
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

**文档版本**: 2.0  
**最后更新**: 2025年9月5日  
**适用版本**: Gei5CMS v1.0.0

## 更新日志

### v2.0 (2025-09-05)
- ✅ 新增用户扩展系统API (User模型、UserRole、UserMeta等)
- ✅ 新增ThemeUserService完整API文档
- ✅ 新增用户相关钩子清单
- ✅ 完善数据模型接口文档
- ✅ 更新服务类接口章节

### v1.0 (2025-09-04) 
- 🔧 初始API和接口文档
- 🔧 钩子系统API文档
- 🔧 基础辅助函数清单