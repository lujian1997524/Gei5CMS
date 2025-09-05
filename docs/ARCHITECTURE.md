# Gei5CMS 架构设计文档

## 📋 项目概述

Gei5CMS 是基于 **Laravel 12.4.0** 开发的通用应用框架，采用"极简核心，主题驱动"的设计理念。

### 核心理念
- **极简核心** - 框架只提供基础服务
- **主题驱动** - 具体应用功能由主题实现  
- **插件扩展** - 通用服务通过插件提供
- **现代架构** - Laravel 原生特性 + 现代化钩子系统

## 🏗️ 实际架构

### 技术栈（已实现）
```yaml
框架版本: Laravel 12.4.0
PHP版本: 8.2+
数据库: MySQL 9.4.0
缓存: Redis
前端: 原生CSS + Tabler Icons
管理后台: macOS 15 风格设计
```

### 核心数据表（已实现）
```sql
-- 用户管理
admin_users              # 管理员用户
admin_user_permissions   # 用户权限

-- 插件系统  
gei5_plugins            # 插件信息
gei5_plugin_data        # 插件数据

-- 主题系统
gei5_themes             # 主题信息

-- 钩子系统
gei5_hooks              # 钩子注册信息

-- 系统设置
gei5_settings           # 系统配置
```

### 目录结构（实际）
```
Gei5CMS/
├── app/
│   ├── Http/Controllers/Admin/    # 管理后台控制器
│   ├── Models/                    # 数据模型
│   ├── Services/                  # 核心服务
│   │   ├── AdminMenuService.php   # 动态菜单服务
│   │   └── HookManager.php        # 钩子管理器
│   ├── Providers/                 # 服务提供者
│   │   ├── HookServiceProvider.php
│   │   ├── PluginServiceProvider.php
│   │   └── ThemeServiceProvider.php
│   ├── Facades/Hook.php           # 钩子门面
│   └── helpers.php                # 全局辅助函数
├── resources/views/admin/         # 管理后台视图
├── routes/admin.php               # 管理路由
├── plugins/                       # 插件目录
├── themes/                        # 主题目录
└── docs/                          # 项目文档
```

## 🔧 核心服务（已实现）

### 1. 管理员认证系统
- **双重登录支持**：用户名/邮箱登录
- **权限管理**：基于角色的访问控制
- **会话管理**：Laravel原生session

### 2. 现代化钩子系统
- **完全Laravel原生**：不兼容WordPress钩子
- **类型安全**：完整PhpDoc注释
- **异步支持**：基于Laravel Queue
- **性能监控**：执行时间统计
- **Blade集成**：`@hook()` 等指令

### 3. 插件管理系统
- **CRUD操作**：完整的插件管理界面
- **状态管理**：激活/停用/优先级
- **文件上传**：支持插件包安装
- **钩子集成**：插件可注册钩子

### 4. 主题管理系统
- **单主题激活**：同时只能激活一个主题
- **预览功能**：主题切换预览
- **文件管理**：主题上传和删除

### 5. 动态菜单系统
- **AdminMenuService**：核心菜单服务
- **钩子驱动**：主题插件可注册菜单
- **权限控制**：菜单项权限验证
- **优先级排序**：自定义菜单顺序

### 6. 管理后台界面
- **macOS 15风格**：现代化设计
- **自适应布局**：双模式显示
  - 初始模式：引导选择主题
  - 业务模式：显示主题相关功能
- **无渐变设计**：纯色简洁风格

## 🎯 设计模式（已实现）

### 1. 服务容器模式
```php
// 钩子系统注册
$this->app->singleton('hook.manager', HookManager::class);

// 门面访问  
Hook::doAction('admin.sidebar.app_menu');
```

### 2. 观察者模式
```php
// 钩子注册
Hook::registerHook('plugin.activated', $callback, 10, 'my_plugin');

// 事件触发
Hook::doAction('plugin.activated', $plugin);
```

### 3. 策略模式
```php
// 菜单渲染策略
AdminMenuService::renderSidebarMenus();

// 不同主题提供不同菜单策略
```

## 🔌 扩展机制（已实现）

### 主题开发
- 主题通过钩子注册菜单
- 主题提供业务控制器和视图  
- 主题激活时自动注册路由

### 插件开发
- 插件通过钩子扩展功能
- 插件提供服务和中间件
- 插件数据独立存储

### 钩子系统
```php
// 标准钩子
Hook::doAction('admin.sidebar.app_menu');
Hook::applyFilters('admin.menu.filter', $menus);

// Blade指令
@hook('admin.sidebar.app_menu')
@filter('content.title', $title)
```

## 📊 管理后台设计

### 导航结构
```
仪表盘 - 数据概览和快捷操作
├── 系统管理
│   ├── 主题管理 - 主题选择和配置
│   ├── 插件管理 - 插件安装和设置  
│   └── 系统设置 - 全局配置
├── 应用管理 (@hook动态加载)
│   └── [由激活主题提供]
└── 用户管理
    └── 管理员管理
```

### 双模式显示
1. **初始引导模式**（无激活主题）
   - 显示主题选择引导
   - 系统状态概览
   - 插件推荐

2. **业务运营模式**（有激活主题）
   - 显示主题相关业务功能
   - 运营数据统计
   - 业务快捷操作

## ⚡ 性能特性（已实现）

### 1. 钩子系统优化
- 内存缓存已注册钩子
- 异步钩子队列执行
- 慢钩子自动警告（>1秒）

### 2. 菜单系统优化
- 优先级自动排序
- 权限过滤缓存
- 延迟加载菜单项

## 🔒 安全机制（已实现）

### 1. 认证授权
- Laravel原生认证
- CSRF保护
- 中间件权限控制

### 2. 输入验证
- 表单请求验证
- 文件上传安全检查
- XSS防护

## 🧪 开发工具（已实现）

### Blade指令
```blade
@hook('hook_name')          # 执行钩子
@filter('filter_name', $value)  # 过滤器
@hookAsync('async_hook')    # 异步钩子
@hasHook('hook_name')       # 检查钩子存在
@hookStats                  # 钩子统计
```

### 辅助函数
```php
do_action('hook_name', $args);
apply_filters('filter_name', $value);
get_plugin_path('plugin_name');
get_theme_path('theme_name');
```

---

**文档版本**: 1.0  
**最后更新**: 2025年9月5日  
**实现状态**: ✅ 已实现核心功能