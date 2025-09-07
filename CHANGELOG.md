# 更新日志

## [2025.9.6] - 强制开发规范更新 v3.0

### 重大规范调整

#### 严格开发规范实施
- **严禁使用 Emoji**: 所有代码、文档、界面文本禁止使用表情符号
- **严禁猜测式开发**: 必须基于官方最新文档进行开发
- **严禁使用 sed 命令**: 禁止使用sed进行文件操作
- **严禁使用渐变效果**: 完全禁止任何形式的渐变样式
- **强制版本更新**: 必须使用2025年最新稳定版本

#### 技术栈强制版本要求
- **Laravel 12.x**: PHP 8.2+强制要求，Carbon 3.x支持
- **Tailwind CSS 4.x**: 零配置，CSS-first配置，现代浏览器要求
- **PHP 8.2**: 强制使用readonly类、联合类型、match表达式
- **Node.js 22+**: TypeScript 5.6+，现代ES特性支持

### 新增功能

#### 系统设置管理界面增强
- **设置搜索功能**: 支持按设置名称、描述、键名实时搜索
- **分组筛选**: 支持按设置分组筛选显示
- **导出导入**: 完整的设置导出导入功能，支持JSON格式
- **批量操作**: 优化批量保存、重置功能和用户体验  
- **实时计数**: 动态显示分组和搜索结果的设置项数量
- **界面优化**: 改进设置项布局和交互体验

#### 仪表盘数据统计增强
- **用户数据统计**: 新增网站用户总数和管理员统计
- **用户趋势分析**: 显示每日新增用户趋势对比
- **活动记录扩展**: 集成用户注册和管理员更新活动
- **快捷操作完善**: 添加用户管理快捷入口
- **数据可视化**: 优化统计卡片图标和趋势显示

#### 用户管理命名优化 
- **命名规范化**: 将"前台用户管理"重命名为"用户管理"，更符合直觉
- **控制器统一**: FrontUserController 重命名为 UserController
- **路由简化**: /admin/front-users 改为 /admin/users
- **界面优化**: 侧边栏菜单和页面标题统一为"用户管理"

#### 用户系统第三阶段完成
- **用户元数据支持**: 新增user_meta表，支持主题扩展字段
- **角色权限系统**: 新增user_roles和user_role_assignments表
- **ThemeUserService API**: 为主题提供完整的用户管理功能
- **钩子系统集成**: 完整的用户生命周期钩子支持

#### 强制架构规范
- **目录结构规范**: 强制服务层、模型层分离
- **命名约定规范**: 禁止缩写，强制描述性命名
- **路由强制规范**: 必须使用资源路由和中间件保护
- **代码质量标准**: 强制类型声明和严格模式

### 技术改进

#### 双用户系统完善
- **管理员管理**: 完善的后台管理员CRUD功能
- **前台用户管理**: 支持元数据和角色的用户管理
- **权限系统**: 基于角色的精细化权限控制
- **主题扩展**: 完整的主题用户系统集成API

#### UI/UX 强制设计规范
- **颜色规范**: 强制使用纯色，严禁渐变效果
- **图标规范**: 强制使用Tabler Icons，禁止色块占位
- **布局规范**: 强制使用现代Flexbox和Grid布局

### 文档更新

#### 新增文档
- **THEME_USER_GUIDE.md**: 主题用户系统集成完整指南
- **强化 API_REFERENCE.md**: 新增ThemeUserService完整API文档
- **更新 USER_SYSTEM_ARCHITECTURE.md**: 第三阶段扩展功能文档

#### 开发工具强制配置
- **VSCode配置**: 强制代码格式化和质量检查
- **Git规范**: 强制语义化提交信息
- **测试要求**: 强制80%以上代码覆盖率

## [2025.9.5] - 用户系统重构 v2.0

### 重大架构调整

#### 权限模板系统移除
- **移除复杂的权限模板系统**: 经过重新评估，权限模板增加了不必要的复杂性
- **回归极简设计**: 保持 Gei5CMS 极简核心的设计理念
- **主题驱动优先**: 权限应该由主题定义，而非框架预设

#### 用户系统职责分离
- **AdminUserController**: 专门管理后台管理员账号
- **FrontUserController**: 管理前台用户（供管理员查看和管理）
- **明确职责边界**: 后台管理 vs 前台用户，各司其职

### 新增功能

#### 双用户系统架构
- **后台管理员系统**：
  - 路由前缀：`/admin/admin-users`
  - 功能：管理员账号创建、权限分配、状态管理
  - 权限：基于具体权限的细粒度控制

- **前台用户管理**：
  - 路由前缀：`/admin/front-users`
  - 功能：查看前台用户、重置密码、邮箱验证管理
  - 扩展性：支持主题自定义用户字段和验证规则

#### 权限系统优化
- **超级管理员**: 拥有所有权限，跳过具体权限检查
- **普通管理员**: 基于具体权限的精细化控制
- **主题权限扩展**: 主题和插件可以注册自己的权限分组

### 技术改进

#### 认证系统分离
- **Web Guard** (`'web'`): 前台用户认证
- **Admin Guard** (`'admin'`): 后台管理员认证
- **清晰隔离**: 前台和后台完全独立的认证流程

#### 主题集成支持
```php
// 主题可以扩展前台用户字段
apply_filters('admin.front_user.editable_fields', $fields, $user);

// 主题可以扩展权限定义
apply_filters('admin.available_permissions', $permissions);

// 主题可以自定义批量操作
apply_filters('admin.front_users.custom_bulk_action', null, $action, $users);
```

#### 钩子系统集成
- **用户生命周期钩子**: 创建、更新、删除等操作的钩子
- **权限变更钩子**: 权限分配和撤销的钩子
- **批量操作钩子**: 支持主题和插件的批量操作扩展

### 路由变更

#### 管理员用户管理
```
旧路由: /admin/users/*
新路由: /admin/admin-users/*

GET    /admin/admin-users          # 管理员列表
POST   /admin/admin-users          # 创建管理员
GET    /admin/admin-users/{id}     # 管理员详情  
PUT    /admin/admin-users/{id}     # 更新管理员
DELETE /admin/admin-users/{id}     # 删除管理员
POST   /admin/admin-users/bulk     # 批量操作
```

#### 新增前台用户管理
```
GET    /admin/front-users          # 前台用户列表
GET    /admin/front-users/{id}     # 用户详情
PUT    /admin/front-users/{id}     # 更新用户信息
DELETE /admin/front-users/{id}     # 删除用户
POST   /admin/front-users/bulk     # 批量操作
POST   /admin/front-users/{id}/reset-password      # 重置密码
POST   /admin/front-users/{id}/toggle-verification # 邮箱验证管理
```

### 权限系统重新设计

#### 简化的权限分组
```php
'system' => [
    'users.view' => '查看管理员',
    'users.create' => '创建管理员',  
    'users.edit' => '编辑管理员',
    'users.delete' => '删除管理员',
    'front_users.view' => '查看前台用户',
    'front_users.edit' => '编辑前台用户',
    'front_users.delete' => '删除前台用户',
],
```

#### 主题权限扩展示例
```php
// 博客主题可以注册内容相关权限
add_filter('admin.available_permissions', function ($permissions) {
    $permissions['content'] = [
        'label' => '内容管理',
        'permissions' => [
            'content.create' => '创建文章',
            'content.edit' => '编辑文章',
            'content.publish' => '发布文章',
        ],
    ];
    return $permissions;
});
```

### 文档更新

- **新增**: `docs/USER_SYSTEM_ARCHITECTURE.md` - 用户系统架构完整设计文档
- **移除**: `docs/PERMISSION_TEMPLATES.md` - 权限模板系统文档（已不再使用）
- **更新**: 开发者集成指南和主题开发最佳实践

### 向后兼容

#### 数据库兼容
- **管理员数据**: 完全保留，无需迁移
- **权限配置**: 现有权限分配保持有效
- **前台用户数据**: 完全不受影响

#### 代码兼容
- **控制器重命名**: `UserController` → `AdminUserController`
- **路由调整**: 旧路由会在后续版本中保持一段时间的兼容性
- **权限标识符**: 保持不变，现有权限配置无需修改

### 破坏性变更

1. **权限模板系统移除**:
   - `config/permissions.php` 配置文件已删除
   - `PermissionTemplateService` 服务类已删除
   - 权限模板相关路由已移除

2. **控制器重命名**:
   - `App\Http\Controllers\Admin\UserController` → `AdminUserController`

3. **路由前缀变更**:
   - `/admin/users/*` → `/admin/admin-users/*`

### 下个版本规划

#### 前台用户系统扩展（已完成）
- 为前台用户提供 meta 字段支持
- 创建主题用户管理 API 组件
- 提供用户角色系统（由主题定义）

#### 主题开发工具
- 用户字段扩展工具包
- 权限管理组件库
- 用户交互界面模板

---

### 设计理念说明

这次重构移除了权限模板系统，回归到 Gei5CMS 的核心设计理念：

1. **极简核心**: 框架只提供必要的基础功能
2. **主题驱动**: 具体的业务逻辑由主题实现
3. **职责分离**: 明确区分管理功能和用户功能

权限模板系统虽然看起来方便，但实际上：
- 增加了框架复杂性
- 与主题驱动架构冲突
- 对于大多数使用场景是过度设计

新的极简权限系统更符合框架定位，让主题开发者有更大的自由度来设计适合自己业务的用户权限体系。

---

**重要提醒**:
- 现有管理员账号和权限完全保留
- 建议主题开发者按照新的架构设计用户交互功能
- 旧的 `/admin/users` 路由将在未来版本中移除，请及时更新

**升级建议**:
- 立即更新管理后台导航链接
- 检查自定义代码中是否有硬编码的路由引用
- 按照新的架构重新设计主题的用户系统