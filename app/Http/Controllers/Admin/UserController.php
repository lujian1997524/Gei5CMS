<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
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

        $query = User::query();

        // 搜索
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 邮箱验证状态筛选
        if ($request->filled('verified')) {
            if ($request->get('verified') === 'yes') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->get('verified') === 'no') {
                $query->whereNull('email_verified_at');
            }
        }

        // 排序
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->get('per_page', 25), 100);
        $users = $query->paginate($perPage);

        // 统计信息
        $stats = [
            'total' => User::count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
            'today_registered' => User::whereDate('created_at', today())->count(),
        ];

        do_action('admin.users.loaded', $users);

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * 显示用户详情
     */
    public function show(User $user)
    {
        do_action('admin.user.showing', $user);

        // 获取用户活动信息
        $userActivity = [
            'registered_at' => $user->created_at,
            'last_updated' => $user->updated_at,
            'email_verified' => $user->email_verified_at,
            // 可以由主题扩展更多活动信息
        ];

        // 让主题可以扩展用户详情数据
        $extendedData = apply_filters('admin.user.detail_data', [], $user);

        return view('admin.users.show', compact('user', 'userActivity', 'extendedData'));
    }

    /**
     * 编辑用户
     */
    public function edit(User $user)
    {
        do_action('admin.user.form.edit', $user);

        // 获取主题定义的用户字段
        $userFields = apply_filters('admin.user.editable_fields', [
            'name' => '姓名',
            'email' => '邮箱',
        ], $user);

        return view('admin.users.edit', compact('user', 'userFields'));
    }

    /**
     * 更新用户信息
     */
    public function update(Request $request, User $user)
    {
        // 基础验证规则
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ];

        // 让主题可以扩展验证规则
        $extendedRules = apply_filters('admin.user.validation_rules', $rules, $user);

        $validator = Validator::make($request->all(), $extendedRules);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        do_action('admin.user.updating', $user, $request->all());

        $updateData = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ];

        // 只有提供密码时才更新
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->input('password'));
        }

        // 让主题可以添加更多字段
        $extendedData = apply_filters('admin.user.update_data', [], $request, $user);
        $updateData = array_merge($updateData, $extendedData);

        $user->update($updateData);

        do_action('admin.user.updated', $user);

        return redirect()->route('admin.users.index')
            ->with('success', '用户信息已更新');
    }

    /**
     * 删除用户
     */
    public function destroy(User $user)
    {
        do_action('admin.user.deleting', $user);

        // 让主题可以在删除前进行清理
        $canDelete = apply_filters('admin.user.can_delete', true, $user);
        
        if (!$canDelete) {
            return back()->with('error', '该用户无法删除');
        }

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

        $users = User::whereIn('id', $ids)->get();

        switch ($action) {
            case 'verify_email':
                User::whereIn('id', $ids)
                    ->whereNull('email_verified_at')
                    ->update(['email_verified_at' => now()]);
                do_action('admin.users.bulk_verified', $users);
                return back()->with('success', "已验证 {$users->count()} 个用户的邮箱");

            case 'unverify_email':
                User::whereIn('id', $ids)->update(['email_verified_at' => null]);
                do_action('admin.users.bulk_unverified', $users);
                return back()->with('success', "已取消 {$users->count()} 个用户的邮箱验证");

            case 'delete':
                // 让主题可以阻止批量删除
                $canBulkDelete = apply_filters('admin.users.can_bulk_delete', true, $users);
                
                if (!$canBulkDelete) {
                    return back()->with('error', '批量删除操作被阻止');
                }

                do_action('admin.users.bulk_deleting', $users);
                User::whereIn('id', $ids)->delete();
                do_action('admin.users.bulk_deleted', $users);
                return back()->with('success', "已删除 {$users->count()} 个用户");

            default:
                // 让主题和插件可以处理自定义批量操作
                $customResult = apply_filters('admin.users.custom_bulk_action', null, $action, $users);
                
                if ($customResult !== null) {
                    return $customResult;
                }
                
                return back()->with('error', '无效的操作');
        }
    }

    /**
     * 重置用户密码
     */
    public function resetPassword(User $user)
    {
        // 生成随机密码
        $newPassword = \Str::random(12);
        
        $user->update([
            'password' => Hash::make($newPassword),
            'email_verified_at' => now(), // 重置密码时自动验证邮箱
        ]);

        do_action('admin.user.password_reset', $user, $newPassword);

        return back()->with('success', "用户密码已重置。新密码：{$newPassword}");
    }

    /**
     * 切换邮箱验证状态
     */
    public function toggleEmailVerification(User $user)
    {
        if ($user->email_verified_at) {
            $user->update(['email_verified_at' => null]);
            $message = '已取消邮箱验证';
            $action = 'unverified';
        } else {
            $user->update(['email_verified_at' => now()]);
            $message = '已验证邮箱';
            $action = 'verified';
        }

        do_action("admin.user.email_{$action}", $user);

        return back()->with('success', $message);
    }
}