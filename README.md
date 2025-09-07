<div align="center">

# Gei5CMS

**多形态Web应用引擎**

基于 Laravel 12.4.0 的极简核心，主题驱动架构  
一套框架，构建博客、电商、论坛、社区等任意Web应用

[快速开始](#快速开始) • [在线演示](#) • [文档](docs/) • [主题市场](#) • [插件生态](#)

</div>

---

## 为什么选择 Gei5CMS？

<table>
<tr>
<td width="50%">

### 极简核心
- **零冗余**：核心只做必要的事
- **高性能**：Laravel 12.4.0 + PHP 8.2
- **现代化**：原生钩子系统，非WordPress兼容

</td>
<td width="50%">

### 主题驱动
- **一套框架**：安装一次，无限可能
- **快速切换**：博客→电商→论坛，一键切换
- **开发友好**：标准化主题开发接口

</td>
</tr>
<tr>
<td width="50%">

### 插件生态
- **丰富扩展**：支付、SEO、分析等通用服务
- **即插即用**：无需修改核心代码
- **开发简单**：现代化插件开发体验

</td>
<td width="50%">

### 管理体验
- **macOS风格**：优雅的管理后台界面
- **双模式**：引导模式 + 运营模式
- **响应式**：完美适配各种设备

</td>
</tr>
</table>

## 应用场景

| 应用类型 | 主题示例 | 适用场景 |
|---------|---------|---------|
| **内容类** | Blog Pro, News Hub | 个人博客、企业官网、新闻资讯 |
| **电商类** | Shop Master, Card Mall | 在线商城、虚拟商品、团购平台 |
| **社交类** | Forum Plus, Community | 论坛社区、问答平台、会员系统 |
| **工具类** | Short Link, Form Builder | 短链服务、表单收集、数据分析 |

## 快速开始

### 环境要求

```
PHP >= 8.2
MySQL >= 8.0  
Redis (推荐)
Web服务器 (Apache/Nginx)
```

### 安装步骤

**第一步：下载文件**
```bash
# 下载并解压到网站目录
wget https://github.com/lujian1997524/Gei5CMS/releases/latest/download/gei5cms.zip
unzip gei5cms.zip -d /your/web/directory/
```

**第二步：设置权限**
```bash
# 设置目录权限
chmod -R 755 /your/web/directory/gei5cms/
chmod -R 777 storage/ bootstrap/cache/ plugins/ themes/
```

**第三步：Web安装**

1. 在浏览器中访问：`http://your-domain.com/install`
2. 按照安装向导完成以下配置：
   - 环境检测（PHP版本、扩展等）
   - 数据库配置（主机、用户名、密码）
   - 管理员账户设置
   - 站点基本信息
3. 安装完成后自动跳转到管理后台

**安装完成！** 开始选择主题，搭建你的应用吧！

### 快速体验

想要快速体验？我们提供了一键Docker部署：

```bash
docker run -d -p 8080:80 gei5cms/gei5cms:latest
# 访问 http://localhost:8080/install 开始安装
```

## 5分钟创建博客主题

```php
// themes/my-blog/Providers/ThemeServiceProvider.php
class BlogThemeProvider extends ServiceProvider 
{
    public function boot()
    {
        // 注册路由
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        
        // 注册视图
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blog');
        
        // 注册管理菜单
        Hook::registerHook('admin.menu.init', function() {
            AdminMenuService::register('blog', [
                'label' => '博客管理',
                'icon' => 'ti ti-edit',
                'children' => [
                    ['label' => '文章管理', 'route' => 'admin.posts.index'],
                    ['label' => '分类管理', 'route' => 'admin.categories.index']
                ]
            ]);
        });
    }
}
```

## 核心特性

### 现代化钩子系统

```php
// 注册钩子
Hook::registerHook('post.created', function($post) {
    // 文章创建后的处理逻辑
    Cache::tags(['posts'])->flush();
    event(new PostCreated($post));
}, 10, 'blog_theme');

// 在模板中使用
@hook('post.sidebar.widgets')
@filter('post.content', $post->content)
```

### 插件开发

```php
// plugins/payment/src/PaymentServiceProvider.php
class PaymentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 注册支付钩子
        Hook::registerHook('order.created', [$this, 'processPayment']);
        
        // 添加管理菜单
        Hook::registerHook('admin.menu.init', function() {
            AdminMenuService::register('payment', [
                'label' => '支付管理',
                'route' => 'admin.payment.index'
            ]);
        });
    }
}
```

## 项目结构

```
Gei5CMS/
├── app/                    # Laravel 应用核心
│   ├── Services/          # 核心服务（钩子、菜单等）
│   ├── Http/Controllers/  # 控制器
│   └── Models/           # 数据模型
├── themes/               # 主题目录
│   └── theme-name/      # 具体主题
├── plugins/             # 插件目录
│   └── plugin-name/    # 具体插件
├── resources/views/admin/ # 管理后台视图
└── docs/               # 项目文档
```

## 文档导航

| 文档 | 描述 |
|------|------|
| [架构设计](docs/ARCHITECTURE.md) | 系统架构和设计理念 |
| [钩子系统](docs/MODERN_HOOK_SYSTEM.md) | 钩子系统完整指南 |
| [用户系统](docs/USER_SYSTEM_ARCHITECTURE.md) | 用户系统架构设计 |
| [API参考](docs/API_REFERENCE.md) | 开发接口文档 |
| [开发流程](docs/DEVELOPMENT_WORKFLOW.md) | 项目开发规范 |

## 开发命令

> 以下命令仅供开发者使用，普通用户请使用Web安装向导

```bash
# 开发环境设置
git clone https://github.com/lujian1997524/Gei5CMS.git
cd Gei5CMS
composer install
cp .env.example .env
php artisan key:generate

# 开发服务器
php artisan serve

# 数据库操作
php artisan migrate
php artisan db:seed

# 缓存清理
php artisan cache:clear
php artisan config:clear

# 钩子调试
php artisan tinker
>>> Hook::getHookStatistics()
```

## 参与贡献

我们欢迎所有形式的贡献！

1. **Fork** 项目到你的账户
2. **创建** 功能分支 (`git checkout -b feature/amazing-feature`)
3. **提交** 你的更改 (`git commit -m 'Add amazing feature'`)
4. **推送** 到分支 (`git push origin feature/amazing-feature`)
5. **创建** Pull Request

## 开源协议

基于 [MIT License](LICENSE) 开源协议

## 致谢

- [Laravel](https://laravel.com) - 优雅的 PHP Web 框架
- [Tabler Icons](https://tabler-icons.io) - 精美的开源图标库

---

<div align="center">

**[官网](#) • [社区](#) • [博客](#) • [支持](#)**

Made with ❤️ by Gei5CMS Team

</div>