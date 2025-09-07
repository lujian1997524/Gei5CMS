# 用户系统架构设计

## 概述

Gei5CMS 采用双用户系统设计，明确区分后台管理员和前台用户的职责，符合主题驱动架构的设计理念。

## 核心设计原则

### 1. 职责分离原则
- **后台管理员**：负责系统管理、内容管理、用户管理等管理功能
- **前台用户**：由主题定义功能和权限，用于前台交互（注册、登录、内容互动等）

### 2. 极简核心原则
- 框架只提供基础的用户模型和管理功能
- 具体的用户交互逻辑由主题实现
- 避免过度设计和不必要的复杂性

### 3. 主题驱动原则
- 前台用户的字段、权限、功能由激活的主题定义
- 不同主题可以有不同的用户需求（博客作者 vs 电商买家 vs 论坛用户）

## 用户系统架构

### 后台管理员系统 (AdminUser)

#### 数据模型
```php
// AdminUser 模型字段
- id: 管理员ID
- name: 管理员姓名  
- email: 邮箱（登录账号）
- username: 用户名（可选登录方式）
- password: 密码
- is_super_admin: 是否超级管理员
- status: 状态（active/inactive/banned）
- avatar: 头像
- last_login_at: 最后登录时间
- last_login_ip: 最后登录IP
```

#### 权限系统
```php
// 权限级别
1. 超级管理员 (is_super_admin = true)
   - 拥有所有权限
   - 不受具体权限限制

2. 普通管理员 (is_super_admin = false)  
   - 通过 admin_user_permissions 表分配具体权限
   - 权限包括：系统管理、用户管理、插件管理、主题管理等
```

#### 管理功能
- **AdminUserController**: 管理后台管理员账号
- **路由前缀**: `/admin/admin-users`
- **权限要求**: 需要 `users.*` 权限

### 前台用户系统 (User)

#### 数据模型
```php
// User 模型字段（Laravel 默认）
- id: 用户ID
- name: 用户姓名
- email: 邮箱
- email_verified_at: 邮箱验证时间
- password: 密码  
- remember_token: 记住登录令牌
- created_at: 注册时间
- updated_at: 更新时间

// 用户元数据表 (user_meta)
- id: 元数据ID
- user_id: 用户ID
- meta_key: 元数据键名
- meta_value: 元数据值
- meta_type: 数据类型 (string/number/boolean/json)
- created_at/updated_at: 时间戳

// 用户角色表 (user_roles) - 由主题定义
- id: 角色ID
- role_slug: 角色标识符
- role_name: 角色名称
- role_description: 角色描述
- permissions: 权限数组 (JSON)
- theme_slug: 所属主题
- is_active: 是否激活
- priority: 优先级

// 用户角色分配表 (user_role_assignments)
- id: 分配ID
- user_id: 用户ID
- role_id: 角色ID
- assigned_at: 分配时间
- assigned_by: 分配者ID
- expires_at: 过期时间（可选）
- role_meta: 角色元数据 (JSON)
```

#### 用户扩展功能

##### 1. 元数据系统
```php
// 用户模型扩展方法
$user = User::find(1);

// 设置元数据
$user->setMeta('profile_avatar', 'avatar.jpg');
$user->setMeta('theme_preferences', ['dark_mode' => true]);
$user->setMeta('vip_level', 3);

// 获取元数据
$avatar = $user->getMeta('profile_avatar');
$allMeta = $user->getAllMeta();

// 批量设置
$user->syncMeta([
    'phone' => '13800138000',
    'address' => '北京市朝阳区',
    'birthday' => '1990-01-01',
]);
```

##### 2. 角色权限系统
```php
// 角色管理
$user = User::find(1);

// 分配角色
$user->assignRole('vip_user');
$user->assignRole('author', ['expires_at' => now()->addDays(30)]);

// 检查角色和权限
if ($user->hasRole('author')) {
    // 用户是作者
}

if ($user->hasPermission('content.publish')) {
    // 用户可以发布内容
}

// 获取用户角色
$roleNames = $user->getRoleNames(); // ['VIP用户', '作者']
$roleSlugs = $user->getRoleSlugs(); // ['vip_user', 'author']

// 获取最高优先级角色
$highestRole = $user->getHighestPriorityRole();
```

##### 3. 主题集成API (ThemeUserService)
```php
// 主题中使用用户管理服务
$themeUserService = app(ThemeUserService::class);

// 创建主题角色
$role = $themeUserService->createRole([
    'role_slug' => 'premium_member',
    'role_name' => '高级会员',
    'role_description' => '享受高级功能的会员用户',
    'permissions' => ['premium.access', 'premium.download'],
    'theme_slug' => 'ecommerce_theme',
    'priority' => 100,
]);

// 批量用户操作
$results = $themeUserService->bulkUserAction([1, 2, 3], 'assign_role', [
    'role_slug' => 'premium_member'
]);

// 用户查询构建器
$query = $themeUserService->getUserQuery([
    'roles' => ['premium_member', 'vip_user'],
    'meta' => ['vip_level' => 3],
    'verified' => true,
    'registered_after' => '2025-01-01',
]);

$users = $query->get();
```

#### 主题扩展机制
```php
// 主题可以通过钩子扩展用户字段
apply_filters('admin.front_user.editable_fields', $fields, $user);

// 主题可以扩展验证规则
apply_filters('admin.front_user.validation_rules', $rules, $user);

// 主题可以扩展用户数据
apply_filters('admin.front_user.update_data', $data, $request, $user);

// 用户角色钩子
do_action('theme.user.role.creating', $roleData);
do_action('theme.user.role.created', $role);
do_action('user.role.assigned', $user, $role, $assignment);

// 用户元数据钩子
do_action('theme.user.meta.updating', $user, $metaData);
do_action('theme.user.meta.updated', $user, $metaData);
```

#### 管理功能
- **FrontUserController**: 管理前台用户（供管理员使用）
- **路由前缀**: `/admin/front-users`
- **权限要求**: 需要 `front_users.*` 权限

## 权限系统设计

### 管理员权限分组

```php
'system' => [
    'label' => '系统管理',
    'permissions' => [
        'settings.view' => '查看设置',
        'settings.edit' => '编辑设置', 
        'users.view' => '查看管理员',
        'users.create' => '创建管理员',
        'users.edit' => '编辑管理员',
        'users.delete' => '删除管理员',
        'front_users.view' => '查看前台用户',
        'front_users.edit' => '编辑前台用户', 
        'front_users.delete' => '删除前台用户',
    ]
],

'plugins' => [
    'label' => '插件管理',
    'permissions' => [
        'plugins.view' => '查看插件',
        'plugins.create' => '安装插件',
        'plugins.edit' => '管理插件',
        'plugins.delete' => '删除插件',
    ]
],

// 主题和插件可以扩展权限分组
$extendedPermissions = apply_filters('admin.available_permissions', $basePermissions);
```

### 权限检查流程

1. **超级管理员**：跳过所有权限检查
2. **普通管理员**：检查具体权限
3. **路由保护**：通过中间件进行权限验证

## 认证系统

### Guard 分离
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users', // 前台用户
    ],
    
    'admin' => [
        'driver' => 'session', 
        'provider' => 'admin_users', // 后台管理员
    ],
],
```

### 登录流程
- **前台用户**：由主题提供登录界面和逻辑
- **后台管理员**：统一的 `/admin/login` 登录入口

## 主题集成指南

### 前台用户注册/登录

主题需要自己实现前台用户的注册和登录功能：

```php
// 主题中的用户注册示例
Route::post('/register', function (Request $request) {
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);
    
    Auth::guard('web')->login($user);
    
    return redirect('/dashboard');
});
```

### 扩展用户字段

主题可以通过元数据系统扩展用户信息：

```php
// 方式1：使用内置元数据系统
$user = User::find(1);
$user->setMeta('profile_bio', '这是用户简介');
$user->setMeta('social_links', [
    'weibo' => 'https://weibo.com/username',
    'wechat' => 'wechat_id'
]);

// 方式2：主题自定义关联表（高级用法）
class UserProfile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// 在主题的服务提供者中注册关联
User::macro('profile', function() {
    return $this->hasOne(UserProfile::class);
});
```

### 主题角色权限系统

主题可以为前台用户定义专属的角色和权限：

```php
// 在主题的 ServiceProvider 中注册角色
use App\Services\ThemeUserService;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $themeUserService = app(ThemeUserService::class);
        
        // 定义主题角色
        $themeUserService->createRole([
            'role_slug' => 'blog_author',
            'role_name' => '博客作者',
            'role_description' => '可以创建和编辑博客文章',
            'permissions' => [
                'blog.post.create',
                'blog.post.edit_own',
                'blog.comment.reply'
            ],
            'theme_slug' => 'blog_theme',
            'priority' => 50,
        ]);
        
        // 注册主题钩子
        $themeUserService->registerThemeHooks('blog_theme');
    }
}

// 在主题控制器中使用权限检查
class BlogController extends Controller
{
    public function createPost(Request $request)
    {
        $user = auth()->user();
        $themeUserService = app(ThemeUserService::class);
        
        if (!$themeUserService->userCan($user, 'blog.post.create')) {
            abort(403, '您没有权限创建文章');
        }
        
        // 创建文章逻辑
    }
}
```

## API 接口

### 管理员用户管理
```
GET    /admin/admin-users          # 管理员列表
POST   /admin/admin-users          # 创建管理员  
GET    /admin/admin-users/{id}     # 管理员详情
PUT    /admin/admin-users/{id}     # 更新管理员
DELETE /admin/admin-users/{id}     # 删除管理员
POST   /admin/admin-users/bulk     # 批量操作
```

### 前台用户管理
```
GET    /admin/front-users          # 前台用户列表
GET    /admin/front-users/{id}     # 用户详情
PUT    /admin/front-users/{id}     # 更新用户
DELETE /admin/front-users/{id}     # 删除用户
POST   /admin/front-users/bulk     # 批量操作
POST   /admin/front-users/{id}/reset-password   # 重置密码
```

### ThemeUserService API
```php
// 角色管理
$themeUserService = app(ThemeUserService::class);

// 创建/更新角色
$role = $themeUserService->createRole(array $roleData);

// 获取主题角色
$roles = $themeUserService->getThemeRoles(string $themeSlug);

// 删除角色
$success = $themeUserService->deleteThemeRole(string $roleSlug, string $themeSlug);

// 用户角色管理
$success = $themeUserService->assignRoleToUser(User $user, string $roleSlug, array $options);
$success = $themeUserService->removeRoleFromUser(User $user, string $roleSlug);

// 用户元数据管理
$success = $themeUserService->setUserMeta(User $user, array $metaData);
$metaData = $themeUserService->getUserMeta(User $user, ?string $key = null);

// 权限检查
$hasPermission = $themeUserService->userCan(User $user, string $permission);

// 用户查询
$query = $themeUserService->getUserQuery(array $filters);

// 批量操作
$results = $themeUserService->bulkUserAction(array $userIds, string $action, array $params);

// 用户统计
$stats = $themeUserService->getUserStats(array $filters);
```

## 最佳实践

### 1. 管理员账号管理
- 保持至少一个超级管理员
- 根据实际职责分配最小必要权限
- 定期审查管理员权限

### 2. 前台用户管理
- 由主题决定用户注册流程和字段
- 管理员可以通过后台查看和管理所有前台用户
- 敏感操作（如密码重置）通过管理后台进行
- 利用元数据系统扩展用户信息，而非修改核心表结构

### 3. 主题开发
- 不要直接修改核心用户模型和表结构
- 通过钩子和过滤器扩展功能
- 使用 ThemeUserService 进行用户管理操作
- 为用户权限设计合理的默认值
- 主题停用时自动禁用相关角色

### 4. 安全考虑
- 前台和后台使用不同的认证 Guard
- 敏感权限只分配给可信的管理员
- 重要操作记录审计日志

## 迁移指南

### 从旧版本升级
1. 旧的 `UserController` 已更名为 `AdminUserController`
2. 路由从 `/admin/users` 更改为 `/admin/admin-users`
3. 新增 `/admin/front-users` 路由用于前台用户管理
4. 权限标识符保持兼容，无需修改现有权限配置

### 注意事项
- 现有管理员账号和权限完全保留
- 前台用户数据不受影响
- 主题需要根据新的架构调整用户相关功能
- 元数据和角色系统完全向后兼容

---

**版本**: v3.0  
**更新时间**: 2025年9月5日  
**设计理念**: 极简核心 + 主题驱动 + 职责分离 + 扩展性优先

## 更新日志

### v3.0 (2025-09-05)
- ✅ 新增用户元数据系统 (user_meta)
- ✅ 新增用户角色权限系统 (user_roles, user_role_assignments)  
- ✅ 扩展 User 模型支持元数据和角色管理
- ✅ 新增 ThemeUserService 提供主题用户管理API
- ✅ 支持主题定义专属角色和权限
- ✅ 完整的钩子系统集成

### v2.0 (2025-09-05)
- ✅ 实现双用户系统设计
- ✅ AdminUserController 专门管理后台管理员
- ✅ FrontUserController 专门管理前台用户
- ✅ 移除复杂的权限模板系统
- ✅ 路由重构和权限简化

### v1.0 (2025-09-04)
- 🔧 初始用户系统设计
- 🔧 基础权限框架