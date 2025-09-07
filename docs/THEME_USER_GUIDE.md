# 主题用户系统集成指南

## 概述

本指南详细说明如何在主题中集成和使用Gei5CMS的用户扩展系统，包括用户元数据、角色权限、以及ThemeUserService API的使用方法。

## 🚀 快速开始

### 1. 在主题服务提供者中注册用户功能

```php
<?php
// themes/my-theme/src/Providers/ThemeServiceProvider.php

namespace MyTheme\Providers;

use App\Services\ThemeUserService;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    protected string $themeSlug = 'my-theme';
    
    public function boot(): void
    {
        $this->registerUserRoles();
        $this->registerUserHooks();
    }
    
    protected function registerUserRoles(): void
    {
        $userService = app(ThemeUserService::class);
        
        // 定义主题用户角色
        $userService->createRole([
            'role_slug' => 'blog_author',
            'role_name' => '博客作者',
            'role_description' => '可以创建和管理博客文章',
            'permissions' => [
                'blog.post.create',
                'blog.post.edit_own',
                'blog.comment.reply',
                'blog.media.upload'
            ],
            'theme_slug' => $this->themeSlug,
            'priority' => 50,
        ]);
        
        $userService->createRole([
            'role_slug' => 'premium_reader',
            'role_name' => '高级读者',
            'role_description' => '可以访问付费内容',
            'permissions' => [
                'blog.premium.read',
                'blog.comment.post'
            ],
            'theme_slug' => $this->themeSlug,
            'priority' => 30,
        ]);
        
        // 注册主题钩子
        $userService->registerThemeHooks($this->themeSlug);
    }
    
    protected function registerUserHooks(): void
    {
        // 用户注册成功后自动分配基础角色
        add_action('user.created', [$this, 'assignDefaultRole']);
        
        // 扩展用户注册字段
        add_filter('admin.front_user.editable_fields', [$this, 'addUserFields']);
    }
    
    public function assignDefaultRole($user): void
    {
        $userService = app(ThemeUserService::class);
        $userService->assignRoleToUser($user, 'premium_reader');
        
        // 设置默认用户元数据
        $userService->setUserMeta($user, [
            'theme_preferences' => ['layout' => 'grid', 'posts_per_page' => 10],
            'notification_settings' => ['email' => true, 'push' => false],
        ]);
    }
    
    public function addUserFields($fields, $user): array
    {
        $fields['bio'] = [
            'type' => 'textarea',
            'label' => '个人简介',
            'required' => false,
            'meta_key' => 'bio'
        ];
        
        $fields['website'] = [
            'type' => 'url',
            'label' => '个人网站',
            'required' => false,
            'meta_key' => 'website'
        ];
        
        return $fields;
    }
}
```

### 2. 在控制器中使用用户权限

```php
<?php
// themes/my-theme/src/Controllers/BlogController.php

namespace MyTheme\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ThemeUserService;
use App\Models\User;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    protected ThemeUserService $userService;
    
    public function __construct(ThemeUserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function createPost(Request $request)
    {
        $user = auth()->user();
        
        // 检查用户权限
        if (!$this->userService->userCan($user, 'blog.post.create')) {
            abort(403, '您没有权限创建文章');
        }
        
        // 创建文章逻辑
        // ...
        
        return view('my-theme::blog.create');
    }
    
    public function showPremiumContent($postId)
    {
        $user = auth()->user();
        
        if (!$user || !$this->userService->userCan($user, 'blog.premium.read')) {
            return view('my-theme::premium.upgrade-required');
        }
        
        // 显示付费内容
        // ...
    }
    
    public function userDashboard()
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // 获取用户角色和权限信息
        $userRoles = $this->userService->getUserThemeRoles($user, 'my-theme');
        $userMeta = $this->userService->getUserMeta($user);
        
        return view('my-theme::user.dashboard', compact('user', 'userRoles', 'userMeta'));
    }
}
```

## 📊 用户数据管理

### 元数据操作示例

```php
<?php

class UserProfileController extends Controller
{
    protected ThemeUserService $userService;
    
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        // 更新用户元数据
        $this->userService->setUserMeta($user, [
            'bio' => $request->bio,
            'website' => $request->website,
            'social_links' => [
                'twitter' => $request->twitter,
                'github' => $request->github,
            ],
            'preferences' => [
                'theme_mode' => $request->theme_mode,
                'email_notifications' => $request->boolean('email_notifications'),
            ]
        ]);
        
        return redirect()->back()->with('success', '个人资料已更新');
    }
    
    public function getProfile()
    {
        $user = auth()->user();
        
        // 获取用户元数据
        $bio = $this->userService->getUserMeta($user, 'bio');
        $preferences = $this->userService->getUserMeta($user, 'preferences', []);
        $allMeta = $this->userService->getUserMeta($user);
        
        return view('my-theme::user.profile', compact('user', 'bio', 'preferences', 'allMeta'));
    }
}
```

### 角色管理示例

```php
<?php

class UserRoleController extends Controller
{
    protected ThemeUserService $userService;
    
    public function upgradeUser(User $user, Request $request)
    {
        $newRole = $request->input('role');
        
        // 验证管理员权限
        if (!auth('admin')->user()->can('front_users.edit')) {
            abort(403);
        }
        
        // 分配新角色（带过期时间）
        $success = $this->userService->assignRoleToUser($user, $newRole, [
            'expires_at' => now()->addMonths(12),
            'role_meta' => [
                'upgrade_reason' => $request->reason,
                'upgraded_by' => auth('admin')->id(),
            ]
        ]);
        
        if ($success) {
            return response()->json(['message' => '用户角色已更新']);
        }
        
        return response()->json(['error' => '角色更新失败'], 400);
    }
    
    public function batchUpgrade(Request $request)
    {
        $userIds = $request->input('user_ids');
        $roleSlug = $request->input('role_slug');
        
        // 批量分配角色
        $results = $this->userService->bulkUserAction($userIds, 'assign_role', [
            'role_slug' => $roleSlug,
            'expires_at' => now()->addMonths(6),
        ]);
        
        return response()->json([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'message' => "成功处理 {$results['success']} 个用户，失败 {$results['failed']} 个"
        ]);
    }
}
```

## 🔍 高级查询和统计

### 用户查询构建器

```php
<?php

class UserAnalyticsService
{
    protected ThemeUserService $userService;
    
    public function __construct(ThemeUserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function getPremiumUsers()
    {
        $query = $this->userService->getUserQuery([
            'roles' => ['premium_reader', 'blog_author'],
            'verified' => true,
            'registered_after' => now()->subMonths(3),
        ]);
        
        return $query->with(['meta', 'activeRoles'])->paginate(20);
    }
    
    public function getActiveAuthors()
    {
        $query = $this->userService->getUserQuery([
            'roles' => ['blog_author'],
            'meta' => [
                'last_post_date' => now()->subMonth()->toDateString()
            ]
        ]);
        
        return $query->get();
    }
    
    public function getUserStatistics()
    {
        // 获取总体统计
        $totalStats = $this->userService->getUserStats();
        
        // 获取付费用户统计
        $premiumStats = $this->userService->getUserStats([
            'roles' => ['premium_reader', 'blog_author']
        ]);
        
        // 获取作者统计
        $authorStats = $this->userService->getUserStats([
            'roles' => ['blog_author'],
            'verified' => true
        ]);
        
        return [
            'total' => $totalStats,
            'premium' => $premiumStats,
            'authors' => $authorStats,
            'conversion_rate' => $premiumStats['total'] > 0 ? 
                round(($premiumStats['total'] / $totalStats['total']) * 100, 2) : 0
        ];
    }
}
```

## 🎨 模板集成

### Blade 模板中的用户权限检查

```blade
{{-- resources/views/my-theme/blog/post.blade.php --}}

@extends('my-theme::layouts.app')

@section('content')
<article class="blog-post">
    <header>
        <h1>{{ $post->title }}</h1>
        <div class="post-meta">
            @if($post->is_premium && !auth()->check())
                <span class="premium-badge">付费内容</span>
            @endif
        </div>
    </header>
    
    <div class="post-content">
        @if($post->is_premium)
            @auth
                @php($userService = app(App\Services\ThemeUserService::class))
                @if($userService->userCan(auth()->user(), 'blog.premium.read'))
                    {!! $post->content !!}
                @else
                    <div class="premium-teaser">
                        {!! Str::limit($post->content, 300) !!}
                        <div class="upgrade-prompt">
                            <h3>升级到高级会员</h3>
                            <p>解锁完整内容和更多专属功能</p>
                            <a href="{{ route('upgrade') }}" class="btn btn-premium">立即升级</a>
                        </div>
                    </div>
                @endif
            @else
                <div class="login-prompt">
                    <p>请 <a href="{{ route('login') }}">登录</a> 查看完整内容</p>
                </div>
            @endauth
        @else
            {!! $post->content !!}
        @endif
    </div>
    
    {{-- 评论区权限控制 --}}
    @auth
        @php($userService = app(App\Services\ThemeUserService::class))
        @if($userService->userCan(auth()->user(), 'blog.comment.post'))
            @include('my-theme::partials.comment-form')
        @else
            <p>升级到高级会员后可以参与评论讨论</p>
        @endif
    @else
        <p>请 <a href="{{ route('login') }}">登录</a> 后参与讨论</p>
    @endauth
</article>
@endsection
```

### 用户仪表盘模板

```blade
{{-- resources/views/my-theme/user/dashboard.blade.php --}}

@extends('my-theme::layouts.app')

@section('content')
<div class="user-dashboard">
    <div class="dashboard-header">
        <h1>个人中心</h1>
        <div class="user-info">
            <img src="{{ $user->getMeta('avatar', '/default-avatar.png') }}" alt="头像" class="avatar">
            <div>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
                @if($user->email_verified_at)
                    <span class="verified-badge">✓ 已验证</span>
                @else
                    <span class="unverified-badge">⚠ 未验证</span>
                @endif
            </div>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="user-roles">
            <h3>我的身份</h3>
            @forelse($userRoles as $role)
                <div class="role-card">
                    <h4>{{ $role['name'] }}</h4>
                    <p>{{ $role['description'] }}</p>
                    <div class="role-permissions">
                        <small>权限级别: {{ $role['priority'] }}</small>
                    </div>
                </div>
            @empty
                <p>暂无特殊身份</p>
            @endforelse
        </div>
        
        <div class="user-meta">
            <h3>个人信息</h3>
            <form action="{{ route('user.profile.update') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>个人简介</label>
                    <textarea name="bio" rows="4">{{ $userMeta['bio'] ?? '' }}</textarea>
                </div>
                
                <div class="form-group">
                    <label>个人网站</label>
                    <input type="url" name="website" value="{{ $userMeta['website'] ?? '' }}">
                </div>
                
                <div class="form-group">
                    <label>主题偏好</label>
                    <select name="theme_mode">
                        <option value="light" {{ ($userMeta['preferences']['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' }}>浅色模式</option>
                        <option value="dark" {{ ($userMeta['preferences']['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' }}>深色模式</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="email_notifications" 
                               {{ ($userMeta['preferences']['email_notifications'] ?? true) ? 'checked' : '' }}>
                        接收邮件通知
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">保存设置</button>
            </form>
        </div>
    </div>
</div>
@endsection
```

## 🔐 权限中间件

### 创建主题权限中间件

```php
<?php
// themes/my-theme/src/Middleware/CheckUserPermission.php

namespace MyTheme\Middleware;

use App\Services\ThemeUserService;
use Closure;
use Illuminate\Http\Request;

class CheckUserPermission
{
    protected ThemeUserService $userService;
    
    public function __construct(ThemeUserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        if (!$this->userService->userCan($user, $permission)) {
            abort(403, '您没有权限执行此操作');
        }
        
        return $next($request);
    }
}
```

### 注册并使用中间件

```php
<?php
// themes/my-theme/src/Providers/ThemeServiceProvider.php

public function boot(): void
{
    // 注册中间件
    $this->app['router']->aliasMiddleware('theme.permission', CheckUserPermission::class);
    
    // 注册路由
    $this->registerRoutes();
}

protected function registerRoutes(): void
{
    Route::group([
        'prefix' => 'blog',
        'namespace' => 'MyTheme\\Controllers',
        'middleware' => 'web'
    ], function () {
        // 需要权限的路由
        Route::middleware('theme.permission:blog.post.create')
            ->get('/create', 'BlogController@create')
            ->name('blog.create');
            
        Route::middleware('theme.permission:blog.premium.read')
            ->get('/premium/{post}', 'BlogController@showPremium')
            ->name('blog.premium.show');
    });
}
```

## 🔧 最佳实践

### 1. 权限设计原则

```php
// 推荐的权限命名规范
$permissions = [
    // 模块.资源.操作
    'blog.post.create',     // 创建博客文章
    'blog.post.edit_own',   // 编辑自己的文章  
    'blog.post.edit_all',   // 编辑所有文章
    'blog.post.delete',     // 删除文章
    'blog.comment.post',    // 发表评论
    'blog.comment.moderate', // 审核评论
    'blog.premium.read',    // 阅读付费内容
    'shop.order.view',      // 查看订单
    'shop.product.buy',     // 购买商品
];
```

### 2. 角色层级设计

```php
// 建议的角色优先级设置
$roles = [
    ['slug' => 'admin', 'priority' => 100],        // 管理员
    ['slug' => 'author', 'priority' => 80],        // 作者
    ['slug' => 'premium_member', 'priority' => 60], // 高级会员
    ['slug' => 'member', 'priority' => 40],        // 普通会员
    ['slug' => 'subscriber', 'priority' => 20],    // 订阅者
];
```

### 3. 性能优化

```php
// 使用缓存减少权限查询
public function checkPermission($user, $permission)
{
    // ThemeUserService 已内置缓存，直接使用即可
    return $this->userService->userCan($user, $permission);
}

// 批量预加载用户数据
$users = User::with(['meta', 'activeRoles'])->get();

// 使用查询构建器而非多次单独查询
$query = $this->userService->getUserQuery([
    'roles' => ['premium_member'],
    'meta' => ['status' => 'active']
]);
$users = $query->paginate(20);
```

### 4. 错误处理

```php
try {
    $success = $this->userService->assignRoleToUser($user, 'premium_member');
    if (!$success) {
        throw new Exception('角色分配失败');
    }
} catch (Exception $e) {
    Log::error('用户角色分配失败', [
        'user_id' => $user->id,
        'role' => 'premium_member',
        'error' => $e->getMessage()
    ]);
    
    return back()->with('error', '操作失败，请稍后重试');
}
```

## 📚 完整示例：会员系统主题

这是一个完整的会员系统主题示例，展示了如何充分利用用户扩展系统：

```php
<?php
// themes/membership-theme/src/Providers/MembershipServiceProvider.php

namespace MembershipTheme\Providers;

use App\Services\ThemeUserService;
use Illuminate\Support\ServiceProvider;

class MembershipServiceProvider extends ServiceProvider
{
    protected string $themeSlug = 'membership-theme';
    
    public function boot(): void
    {
        $this->setupMembershipRoles();
        $this->registerMembershipHooks();
        $this->registerMembershipRoutes();
    }
    
    protected function setupMembershipRoles(): void
    {
        $userService = app(ThemeUserService::class);
        
        // 基础会员
        $userService->createRole([
            'role_slug' => 'basic_member',
            'role_name' => '基础会员',
            'permissions' => ['membership.basic_content.read'],
            'theme_slug' => $this->themeSlug,
            'priority' => 30,
        ]);
        
        // 高级会员
        $userService->createRole([
            'role_slug' => 'premium_member', 
            'role_name' => '高级会员',
            'permissions' => [
                'membership.basic_content.read',
                'membership.premium_content.read',
                'membership.download.unlimited'
            ],
            'theme_slug' => $this->themeSlug,
            'priority' => 60,
        ]);
        
        // VIP会员
        $userService->createRole([
            'role_slug' => 'vip_member',
            'role_name' => 'VIP会员',
            'permissions' => ['*'], // 所有权限
            'theme_slug' => $this->themeSlug,
            'priority' => 90,
        ]);
        
        $userService->registerThemeHooks($this->themeSlug);
    }
    
    protected function registerMembershipHooks(): void
    {
        // 新用户注册时自动设为基础会员
        add_action('user.created', function($user) {
            $userService = app(ThemeUserService::class);
            $userService->assignRoleToUser($user, 'basic_member');
            
            // 设置会员信息
            $userService->setUserMeta($user, [
                'membership_start_date' => now()->toDateString(),
                'membership_status' => 'active',
                'download_credits' => 10, // 基础会员10个下载额度
            ]);
        });
        
        // 会员升级处理
        add_action('membership.upgraded', function($user, $newRole) {
            $userService = app(ThemeUserService::class);
            
            $credits = match($newRole) {
                'premium_member' => 100,
                'vip_member' => -1, // 无限制
                default => 10
            };
            
            $userService->setUserMeta($user, [
                'download_credits' => $credits,
                'upgrade_date' => now()->toDateString(),
            ]);
        });
    }
}
```

---

**文档版本**: 1.0  
**最后更新**: 2025年9月5日  
**适用于**: Gei5CMS v1.0.0 用户扩展系统