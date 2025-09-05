# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

此文件为 Claude Code (claude.ai/code) 在此代码库中工作提供指导。

## macOS开发环境配置

### 系统要求
- macOS (当前开发环境)
- Homebrew包管理器
- 所有服务直接安装到本地系统

### 安装配置清单

#### PHP 8.2
```bash
# 安装PHP 8.2
brew install php@8.2

# 配置路径
export PATH="/opt/homebrew/bin:$PATH"
export PATH="/opt/homebrew/sbin:$PATH"

# 安装路径: /opt/homebrew/etc/php/8.2/
# 配置文件: /opt/homebrew/etc/php/8.2/php.ini
# 启动方式: brew services start php@8.2
```

#### MySQL 9.4.0
```bash
# 安装MySQL
brew install mysql

# 启动服务
brew services start mysql

# 安装路径: /opt/homebrew/Cellar/mysql/9.4.0_3/
# 配置文件: /opt/homebrew/etc/my.cnf
# 数据目录: /opt/homebrew/var/mysql/
# 日志文件: /opt/homebrew/var/mysql/*.log
```

**MySQL实际配置**:
- 版本: 9.4.0
- 端口: 3306
- 用户: root
- 密码: 无 (开发环境，需运行mysql_secure_installation设置)
- 数据库: gei5cms (待创建)
- 表前缀: gei5_

#### Redis 8.2.1
```bash
# 安装Redis
brew install redis

# 启动服务
brew services start redis

# 安装路径: /opt/homebrew/Cellar/redis/8.2.1/
# 配置文件: /opt/homebrew/etc/redis.conf
# 数据目录: /opt/homebrew/var/db/redis/
```

**Redis实际配置**:
- 版本: 8.2.1
- 端口: 6379
- 密码: 无 (开发环境)
- 数据库: 0 (默认)

#### phpMyAdmin 5.2.2
```bash
# 安装phpMyAdmin
brew install phpmyadmin

# 配置Nginx/Apache虚拟主机
# 访问地址: http://localhost/phpmyadmin
# 安装路径: /opt/homebrew/Cellar/phpmyadmin/5.2.2/
# 配置文件: /opt/homebrew/etc/phpmyadmin.config.inc.php
```

**phpMyAdmin实际配置**:
- 版本: 5.2.2
- 安装路径: /opt/homebrew/share/phpmyadmin/
- 配置文件: /opt/homebrew/etc/phpmyadmin.config.inc.php
- 访问地址: 需配置Apache虚拟主机

#### Composer 2.8.11
```bash
# 安装Composer
brew install composer

# 全局路径: /opt/homebrew/bin/composer
# 全局配置: ~/.composer/
```

**Composer实际配置**:
- 版本: 2.8.11
- 安装路径: /opt/homebrew/bin/composer
- PHP版本依赖: 8.4.12 (需调整为使用PHP 8.2)

### 服务管理命令

#### 启动所有服务
```bash
# 启动PHP-FPM
brew services start php@8.2

# 启动MySQL
brew services start mysql

# 启动Redis
brew services start redis
```

#### 停止所有服务
```bash
# 停止PHP-FPM
brew services stop php@8.2

# 停止MySQL
brew services stop mysql

# 停止Redis
brew services stop redis
```

#### 查看服务状态
```bash
# 查看所有Homebrew服务状态
brew services list

# 查看特定服务状态
brew services list | grep php
brew services list | grep mysql
brew services list | grep redis
```

### 环境变量配置

在 `~/.zshrc` 或 `~/.bash_profile` 中添加:
```bash
# PHP路径
export PATH="/opt/homebrew/bin:$PATH"
export PATH="/opt/homebrew/sbin:$PATH"

# 确保使用正确的PHP版本
alias php="/opt/homebrew/bin/php"
alias composer="/opt/homebrew/bin/composer"
```

### 开发工具
- **IDE**: VS Code / PhpStorm
- **数据库管理**: phpMyAdmin (http://localhost/phpmyadmin)
- **API测试**: Postman / Insomnia
- **版本控制**: Git (系统自带)

## 开发执行要求

**进度跟踪强制规范**：
- 每完成一个开发小阶段必须进行标注和记录
- 使用TodoWrite工具追踪所有开发任务进度
- 完成阶段性任务后立即标记为completed
- 遇到问题或阻碍时详细记录和报告
- 严格按照DEVELOPMENT_WORKFLOW_SPECIFICATION.md执行开发流程

**开发授权确认**：
- 用户已明确授权开始项目开发
- 按照16周开发计划执行
- 从阶段一第1周项目初始化开始
- 每个里程碑完成后需要验收确认

## 项目状态

**当前阶段：开发执行阶段**
- ✅ 完整的技术规范和架构设计
- ✅ 开发流程规范文档
- ➡️ 开始代码实现：阶段一第1周项目初始化
- ❌ 严格遵循开发流程，每阶段必须标注进度

## 项目概述

Gei5CMS 是基于 Laravel 12 构建的**通用应用框架**，采用"极简核心"设计理念。该系统作为基础框架，可通过不同主题实现各种应用类型 - 从博客、电商网站到论坛、发卡平台、教育网站和社交网络。

## 核心架构原则

### 极简主义设计
- 核心仅包含8张数据表（用户、设置、插件、主题、钩子、API端点、权限、角色）
- 所有业务功能均由主题和插件提供
- 核心只提供框架服务：用户管理、插件/主题系统、钩子、缓存、API基础设施

### 主题驱动应用
不同主题创建完全不同的应用类型：
- **博客主题**：创建文章、分类、标签表
- **电商主题**：创建商品、订单、购物车表
- **论坛主题**：创建版块、主题帖、回复表  
- **发卡主题**：创建卡密、交易、库存表

### 灵活的功能实现
主题可以选择两种实现方式：
1. **插件依赖**：轻量化主题，依赖支付、短信、邮件插件
2. **一体化功能**：内置支付、通知系统的完整主题

## 核心技术栈

- **后端**：Laravel 12、PHP 8.2+、MySQL 8.0+、Redis 7.0+
- **管理界面**：Livewire 3.0、Alpine.js、Tailwind CSS 4.0
- **性能优化**：Laravel Octane、APCu + Redis 多层缓存
- **安全机制**：Laravel Sanctum、自定义ACL系统、插件沙箱

## 数据库前缀

所有表使用 `gei5_` 前缀。核心表固定，业务表由主题动态创建。

## 钩子系统架构

系统提供500+预定义钩子，覆盖所有层级：
- 系统生命周期钩子（`gei5_system_init`、`gei5_request_start`）
- 插件管理钩子（`gei5_plugin_install`、`gei5_plugin_activate`）  
- 主题钩子（`gei5_theme_activate`、`gei5_theme_deactivate`）
- 多语言钩子（`gei5_translate`、`gei5_language_switch`）
- 性能监控钩子（`gei5_hook_performance_monitor`）
- 安全钩子（`gei5_security_event`、`gei5_permission_violation`）

钩子系统支持：
- 异步执行提升性能
- 基于优先级的执行顺序
- 自动性能监控和优化
- 昂贵操作的结果缓存

## 安装系统

- 传统PHP安装方式（非Docker）
- 基于Composer的依赖管理
- 根据激活的主题/插件自动创建数据表
- 参考WordPress的升级机制，自动备份

## 插件主题市场集成

核心提供基础API端点用于：
- 搜索和发现插件/主题
- 版本兼容性检查
- 下载和安装
- 付费扩展的许可证验证

实际的应用市场是独立系统，处理开发者账户、代码审核、支付和分发。

## 强制性开发规范

### 绝对禁止的行为
1. **禁止使用emoji表情符号** - 所有代码、注释、文档、变量名中严禁使用任何emoji
2. **禁止猜测式开发** - 不允许基于假设或过时信息进行开发，必须基于确定的规范和文档
3. **禁止版本语法错误** - 使用技术栈时必须确认正确的版本语法和API变化
4. **禁止低级错误** - 严格检查语法错误、拼写错误、逻辑错误

### 强制性文档维护
- **新增钩子必须文档化**：每次创建新钩子时，必须按照HOOKS_INTERFACES_API_REFERENCE.md的格式要求同步更新文档
- **新增API必须文档化**：每次创建新API端点时，必须在HOOKS_INTERFACES_API_REFERENCE.md中记录完整的接口规范
- **接口变更必须文档化**：修改现有钩子或API时，必须同步更新对应文档

### 版本兼容性检查
在使用以下技术栈时，必须确认版本语法：
- **Laravel 12.x** (发布日期: 2025年2月24日) - 支持PHP 8.2-8.4，确认新版本的配置语法、路由语法、中间件语法变化
- **Livewire v3.6.4** (最新版本: 2025年7月) - 注意与2.x版本的语法差异，支持Laravel ^10.0|^11.0|^12.0
- **Tailwind CSS v4.1** (官方发布: 2025年) - 零配置模式，仅需@import "tailwindcss"，注意配置方式重大变化
- **Alpine.js v3.15.0** (最新版本: 2025年) - 确认版本兼容的指令语法
- **PHP 8.2** (项目标准版本) - 使用PHP 8.2特性和语法，确保兼容性

### Tailwind CSS v4.1 强制语法规范

**禁止使用v3.x旧语法**：
```css
/* ❌ 禁止 - v3.x 语法 */
@tailwind base;
@tailwind components;  
@tailwind utilities;
```

**必须使用v4.1新语法**：
```css
/* ✅ 必须 - v4.1 语法 */
@import "tailwindcss";
```

**主题配置方式**：
```css
@theme {
  /* 颜色变量 */
  --color-primary: hsl(210, 100%, 50%);
  --color-secondary: hsl(160, 100%, 40%);
  --color-neutral-50: #f9fafb;
  
  /* 间距变量 */
  --spacing-xs: 0.5rem;
  --spacing-xl: 2rem;
  
  /* 字体变量 */
  --font-heading: "Inter", system-ui, sans-serif;
  
  /* 容器变量 */
  --container-prose: 65ch;
}
```

**自定义工具类**：
```css
@utility btn-primary {
  background-color: var(--color-primary);
  color: white;
  padding: var(--spacing-xs) var(--spacing-xl);
  border-radius: 0.375rem;
  
  &:hover {
    opacity: 0.9;
  }
}
```

**CSS变量访问**：
```css
.custom-component {
  background-color: var(--color-primary);
  padding: var(--spacing-xl);
  font-family: var(--font-heading);
}
```

**暗色模式配置**：
```css
@layer theme {
  :root, :host {
    @variant dark {
      --color-primary: #7718b0;
      --color-background: #1a1a1a;
    }
  }
}
```

## 开发约束

**重要**：未经用户明确许可，不得初始化任何Laravel项目或创建代码文件。项目目前处于设计阶段，仅交付文档。

## 文档文件

- `CMS_TECHNICAL_SPECIFICATION.md`：完整技术架构规范
- `UI_UX_DESIGN_SPECIFICATION.md`：管理界面设计标准  
- `INSTALLATION_DEPLOYMENT_SPECIFICATION.md`：安装系统设计
- `HOOKS_INTERFACES_API_REFERENCE.md`：开发者钩子和API完整参考

## 开发工作流程

### 文档阅读顺序
1. **CMS_TECHNICAL_SPECIFICATION.md** - 理解整体架构和技术实现
2. **UI_UX_DESIGN_SPECIFICATION.md** - 了解管理界面设计规范（基于Backpack for Laravel）
3. **INSTALLATION_DEPLOYMENT_SPECIFICATION.md** - 掌握安装部署机制
4. **HOOKS_INTERFACES_API_REFERENCE.md** - 熟悉500+钩子和API端点

### 开发准备工作
当获得开发授权后，需要：
1. 创建Laravel 12项目基础结构
2. 配置统一的`gei5_`数据表前缀
3. 实现8张核心框架表的迁移文件
4. 搭建基于Livewire 3.0 + Alpine.js + Tailwind CSS 4.0的管理界面
5. 实现插件/主题系统核心类

### 关键实现注意事项
- 严格遵循极简核心原则：核心仅包含框架服务，业务逻辑由主题实现
- 插件系统必须实现沙箱隔离机制
- 钩子系统支持异步执行和优先级排序
- 所有组件必须支持多语言（中文/英文）
- 管理界面严格遵循Backpack设计风格：Bootstrap 5 + Alpine.js
- **严格执行强制性开发规范**：禁用emoji、禁止猜测、强制文档化、版本检查

## 设计哲学

"做最少的事，做最好的事" - 提供强大的插件/主题框架，让社区创造无限可能。核心应保持极简，同时为开发者提供丰富的扩展点。