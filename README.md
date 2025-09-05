# Gei5CMS - 现代化内容管理系统

## 项目简介

Gei5CMS 是基于 Laravel 12.4.0 开发的现代化内容管理系统，采用"极简核心，主题驱动"的设计理念。通过不同主题可以构建博客、电商、论坛、社区等任意类型的Web应用。

### 核心特性

- **极简核心** - 仅提供基础框架服务，业务逻辑完全由主题实现
- **主题驱动** - 一套框架，无限应用可能
- **插件扩展** - 丰富的插件生态，提供通用服务支持  
- **现代架构** - Laravel 12 + 现代化钩子系统
- **优雅界面** - macOS 15 风格的管理后台

## 快速开始

### 环境要求

```
PHP >= 8.2
MySQL >= 8.0
Redis (推荐)
Composer
```

### 安装步骤

```bash
# 克隆项目
git clone <repository-url> gei5cms
cd gei5cms

# 安装依赖
composer install

# 环境配置
cp .env.example .env
php artisan key:generate

# 数据库配置
php artisan migrate

# 启动开发服务器
php artisan serve
```

### 初始化管理员

```bash
# 创建默认管理员账户
php artisan db:seed --class=AdminUserSeeder

# 或访问: http://localhost:8000/admin/create-default-admin
```

默认管理员：
- 用户名: `admin` 
- 密码: `password`

## 项目架构

### 核心模块

```
Gei5CMS
├── 用户认证系统      # 管理员登录、权限管理
├── 主题管理系统      # 主题切换、配置、预览
├── 插件管理系统      # 插件安装、激活、配置
├── 现代钩子系统      # Laravel原生钩子机制
├── 动态菜单系统      # 主题插件动态注册菜单
└── 管理后台界面      # macOS风格现代化界面
```

### 支持的应用类型

通过不同主题，可以快速构建：

- **内容类**: 博客、新闻、知识库、文档站
- **电商类**: 商城、发卡、团购、分类信息  
- **社交类**: 论坛、社区、问答、会员系统
- **工具类**: 短链、表单、数据分析、API服务

## 主题开发

### 主题结构

```
themes/my-theme/
├── theme.json          # 主题配置文件
├── admin/             # 管理后台相关
│   ├── Controllers/   # 业务控制器
│   ├── Views/         # 管理界面  
│   └── routes.php     # 后台路由
├── public/            # 前台相关
│   ├── Controllers/   # 前台控制器
│   ├── Views/         # 前台模板
│   └── assets/        # 静态资源
└── Providers/         # 服务提供者
    └── ThemeServiceProvider.php
```

### 主题注册菜单

```php
// 在主题服务提供者中
Hook::registerHook('admin.menu.init', function() {
    AdminMenuService::register('blog_menu', [
        'key' => 'blog-management',
        'label' => '博客管理',
        'icon' => 'ti ti-file-text',
        'children' => [
            [
                'key' => 'posts',
                'label' => '文章管理',
                'route' => 'admin.blog.posts.index',
                'icon' => 'ti ti-edit'
            ]
        ]
    ]);
});
```

## 插件开发

### 插件结构

```
plugins/my-plugin/
├── plugin.json        # 插件配置
├── src/              # 源代码
│   ├── Controllers/  # 控制器
│   ├── Models/       # 模型
│   ├── Services/     # 服务类
│   └── Providers/    # 服务提供者
├── resources/        # 资源文件
│   ├── views/        # 视图模板
│   └── assets/       # 静态资源
└── database/         # 数据库文件
    └── migrations/   # 数据迁移
```

### 插件注册钩子

```php
// 插件服务提供者
class PaymentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 注册支付钩子
        Hook::registerHook('order.created', [$this, 'processPayment'], 10, 'payment_plugin');
        
        // 注册管理菜单
        Hook::registerHook('admin.menu.init', [$this, 'registerMenus']);
    }
}
```

## 钩子系统

### 基础用法

```php
// 注册钩子
Hook::registerHook('user.created', function($user) {
    // 用户创建后的处理逻辑
}, 10, 'my_plugin');

// 触发动作钩子
Hook::doAction('user.created', $user);

// 应用过滤器钩子  
$title = Hook::applyFilters('post.title', $originalTitle, $post);
```

### Blade指令

```blade
{{-- 执行钩子 --}}
@hook('admin.sidebar.app_menu')

{{-- 条件钩子 --}}
@hasHook('custom.feature')
    <div>自定义功能可用</div>
@endhasHook

{{-- 过滤器输出 --}}
@filter('content.excerpt', $post->content)
```

## 文档

- [架构设计](docs/ARCHITECTURE.md) - 系统架构和核心概念
- [钩子系统](docs/MODERN_HOOK_SYSTEM.md) - 现代化钩子系统详解
- [API参考](docs/API_REFERENCE.md) - 接口和钩子完整清单

## 开发环境

### 开发命令

```bash
# 启动开发服务器
php artisan serve

# 数据库迁移
php artisan migrate

# 清除缓存
php artisan cache:clear
php artisan config:clear

# 钩子系统调试
php artisan tinker
>>> Hook::getHookStatistics()
```

### 目录权限

```bash
# 设置存储目录权限
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chmod -R 775 plugins/
chmod -R 775 themes/
```

## 贡献指南

欢迎贡献代码！请遵循以下步骤：

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)  
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

## 开源协议

本项目基于 MIT 协议开源 - 查看 [LICENSE](LICENSE) 文件了解详情。

## 致谢

- [Laravel](https://laravel.com) - 优雅的PHP Web框架
- [Tabler Icons](https://tabler-icons.io) - 美观的开源图标库

---

**版本**: v1.0.0  
**最后更新**: 2025年9月5日  
**维护者**: Gei5CMS Team