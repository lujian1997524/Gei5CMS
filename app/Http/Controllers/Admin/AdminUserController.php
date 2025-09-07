<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    public function __construct()
    {
        // 权限中间件已在路由中设置
    }

    /**
     * 用户列表
     */
    public function index(Request $request)
    {
        do_action('admin.users.loading');

        $query = AdminUser::query();

        // 搜索
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 排序
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->get('per_page', 25), 100);
        $users = $query->paginate($perPage);

        // 统计信息
        $stats = [
            'total' => AdminUser::count(),
            'active' => AdminUser::where('status', 'active')->count(),
            'inactive' => AdminUser::where('status', 'inactive')->count(),
            'super_admins' => AdminUser::where('is_super_admin', true)->count(),
        ];

        do_action('admin.users.loaded', $users);

        return view('admin.admin-users.index', compact('users', 'stats'));
    }

    /**
     * 创建用户表单
     */
    public function create()
    {
        $user = new AdminUser();
        
        // 获取权限分组（由激活主题和插件提供）
        $availablePermissions = $this->getAvailablePermissions();
        
        do_action('admin.user.form.create');

        return view('admin.admin-users.create', compact('user', 'availablePermissions'));
    }

    /**
     * 存储新用户
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admin_users,email',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive',
            'is_super_admin' => 'boolean',
            'permissions' => 'array', // 可选的具体权限
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        do_action('admin.user.creating', $request->all());

        $user = AdminUser::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'status' => $request->input('status'),
            'is_super_admin' => $request->boolean('is_super_admin'),
        ]);

        // 如果不是超级管理员，则分配具体权限
        if (!$request->boolean('is_super_admin')) {
            $permissions = $request->input('permissions', []);
            $user->permissions()->sync($permissions);
        }

        do_action('admin.user.created', $user);

        return redirect()->route('admin.users.index')
            ->with('success', '用户创建成功');
    }

    /**
     * 显示用户详情
     */
    public function show(AdminUser $user)
    {
        do_action('admin.user.showing', $user);

        // 获取用户活动记录（最近登录、操作历史等）
        $recentActivity = [
            'last_login' => $user->last_login_at,
            'login_count' => $user->login_count ?? 0,
            'created_at' => $user->created_at,
        ];

        return view('admin.users.show', compact('user', 'recentActivity'));
    }

    /**
     * 编辑用户表单
     */
    public function edit(AdminUser $user)
    {
        // 获取权限分组（由激活主题和插件提供）
        $availablePermissions = $this->getAvailablePermissions();
        
        do_action('admin.user.form.edit', $user);

        return view('admin.users.edit', compact('user', 'availablePermissions'));
    }

    /**
     * 更新用户信息
     */
    public function update(Request $request, AdminUser $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admin_users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:active,inactive',
            'is_super_admin' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        do_action('admin.user.updating', $user, $request->all());

        $updateData = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'status' => $request->input('status'),
            'is_super_admin' => $request->boolean('is_super_admin'),
        ];

        // 只有提供密码时才更新
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->input('password'));
        }

        $user->update($updateData);

        do_action('admin.user.updated', $user);

        return redirect()->route('admin.users.index')
            ->with('success', '用户信息已更新');
    }

    /**
     * 删除用户
     */
    public function destroy(AdminUser $user)
    {
        // 防止删除当前登录用户
        if ($user->id === auth('admin')->id()) {
            return back()->with('error', '不能删除当前登录用户');
        }

        // 防止删除最后一个超级管理员
        if ($user->is_super_admin) {
            $superAdminCount = AdminUser::where('is_super_admin', true)->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', '不能删除最后一个超级管理员');
            }
        }

        do_action('admin.user.deleting', $user);

        $user->delete();

        do_action('admin.user.deleted', $user);

        return redirect()->route('admin.users.index')
            ->with('success', '用户已删除');
    }

    /**
     * 批量操作
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', '请选择要操作的用户');
        }

        // 防止操作当前登录用户
        $currentUserId = auth('admin')->id();
        if (in_array($currentUserId, $ids)) {
            return back()->with('error', '不能对当前登录用户执行批量操作');
        }

        $users = AdminUser::whereIn('id', $ids)->get();

        switch ($action) {
            case 'activate':
                AdminUser::whereIn('id', $ids)->update(['status' => 'active']);
                do_action('admin.users.bulk_activated', $users);
                return back()->with('success', "已激活 {$users->count()} 个用户");

            case 'deactivate':
                AdminUser::whereIn('id', $ids)->update(['status' => 'inactive']);
                do_action('admin.users.bulk_deactivated', $users);
                return back()->with('success', "已停用 {$users->count()} 个用户");

            case 'delete':
                // 检查是否包含超级管理员
                $superAdmins = $users->where('is_super_admin', true);
                if ($superAdmins->count() > 0) {
                    $totalSuperAdmins = AdminUser::where('is_super_admin', true)->count();
                    if ($totalSuperAdmins - $superAdmins->count() < 1) {
                        return back()->with('error', '操作会删除所有超级管理员，不允许执行');
                    }
                }

                do_action('admin.users.bulk_deleting', $users);
                AdminUser::whereIn('id', $ids)->delete();
                do_action('admin.users.bulk_deleted', $users);
                return back()->with('success', "已删除 {$users->count()} 个用户");

            default:
                return back()->with('error', '无效的操作');
        }
    }

    /**
     * 用户权限管理
     */
    public function permissions(AdminUser $user)
    {
        do_action('admin.user.permissions.showing', $user);

        // 获取用户的权限信息
        $userPermissions = $user->permissions ?? [];
        
        // 系统可用权限列表
        $availablePermissions = $this->getAvailablePermissions();

        return view('admin.users.permissions', compact('user', 'userPermissions', 'availablePermissions'));
    }

    /**
     * 更新用户权限
     */
    public function updatePermissions(Request $request, AdminUser $user)
    {
        $permissions = $request->input('permissions', []);

        do_action('admin.user.permissions.updating', $user, $permissions);

        // 更新用户权限（这里需要根据实际的权限表结构来实现）
        // 暂时使用JSON字段存储
        $user->update(['permissions' => $permissions]);

        do_action('admin.user.permissions.updated', $user);

        return back()->with('success', '用户权限已更新');
    }

    /**
     * 获取可用权限列表
     */
    protected function getAvailablePermissions()
    {
        // 基础权限（框架核心）
        $basePermissions = [
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
                    'logs.view' => '查看日志',
                    'tools.view' => '系统工具',
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
            'themes' => [
                'label' => '主题管理',
                'permissions' => [
                    'themes.view' => '查看主题',
                    'themes.create' => '安装主题',
                    'themes.edit' => '管理主题',
                    'themes.activate' => '激活主题',
                ]
            ],
            'media' => [
                'label' => '媒体库',
                'permissions' => [
                    'media.view' => '查看媒体',
                    'media.upload' => '上传媒体',
                    'media.delete' => '删除媒体',
                ]
            ],
            'hooks' => [
                'label' => '钩子管理',
                'permissions' => [
                    'hooks.view' => '查看钩子',
                    'hooks.edit' => '管理钩子',
                ]
            ]
        ];

        // 让主题和插件可以扩展权限
        $extendedPermissions = apply_filters('admin.available_permissions', $basePermissions);

        return $extendedPermissions;
    }
}