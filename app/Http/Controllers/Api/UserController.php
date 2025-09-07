<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class UserController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AdminUser::query();

            // 应用过滤器
            $query = $this->applyFilters($query, $request, [
                'status' => 'exact',
                'name' => 'like',
                'email' => 'like',
                'is_super_admin' => 'exact',
            ]);

            // 应用排序
            $query = $this->applySorting($query, $request, [
                'id', 'name', 'email', 'status', 'created_at', 'updated_at', 'last_login_at'
            ]);

            // 选择字段（排除敏感信息）
            $query->select([
                'id', 'name', 'username', 'email', 'avatar', 'status', 
                'is_super_admin', 'last_login_at', 'last_login_ip', 
                'created_at', 'updated_at'
            ]);

            // 返回分页结果
            return $this->paginatedResponse($query, $request, 'Users retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users: ' . $e->getMessage(), 500);
        }
    }

    public function show(int $userId): JsonResponse
    {
        try {
            $user = AdminUser::select([
                'id', 'name', 'username', 'email', 'avatar', 'status', 
                'is_super_admin', 'last_login_at', 'last_login_ip', 
                'created_at', 'updated_at'
            ])->find($userId);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // 获取用户权限
            $permissions = $user->getPermissionNames();

            // 获取头像URL
            $avatarUrl = $user->getAvatarUrlAttribute();

            $userData = array_merge($user->toArray(), [
                'permissions' => $permissions,
                'avatar_url' => $avatarUrl,
                'tokens_count' => $user->tokens()->count(),
            ]);

            return $this->successResponse($userData, 'User details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateRequest($request, [
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:100|unique:admin_users,username',
                'email' => 'required|email|max:255|unique:admin_users,email',
                'password' => 'required|string|min:8|confirmed',
                'status' => 'sometimes|in:active,inactive,suspended',
                'is_super_admin' => 'sometimes|boolean',
                'permissions' => 'sometimes|array',
                'permissions.*' => 'string',
                'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB
            ]);

            // 只有超级管理员才能创建超级管理员
            if (isset($data['is_super_admin']) && $data['is_super_admin'] && !auth()->user()->is_super_admin) {
                return $this->forbiddenResponse('Only super administrators can create super administrators');
            }

            // 处理头像上传
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $filename = 'avatar_' . time() . '.' . $avatar->getClientOriginalExtension();
                $avatarPath = $avatar->storeAs('avatars', $filename, 'public');
            }

            // 创建用户
            $user = AdminUser::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
                'is_super_admin' => $data['is_super_admin'] ?? false,
                'avatar' => $avatarPath ? 'storage/' . $avatarPath : null,
            ]);

            // 分配权限
            if (isset($data['permissions']) && !empty($data['permissions'])) {
                $user->syncPermissions($data['permissions']);
            }

            do_action('user.created', $user, $request);

            return $this->successResponse([
                'user' => $this->transformResource($user),
                'permissions' => $user->getPermissionNames(),
            ], 'User created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $userId): JsonResponse
    {
        try {
            $user = AdminUser::find($userId);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // 防止用户修改自己的超级管理员状态
            if ($userId == auth()->id() && $request->has('is_super_admin')) {
                return $this->forbiddenResponse('You cannot modify your own super administrator status');
            }

            // 只有超级管理员才能修改超级管理员状态
            if ($request->has('is_super_admin') && !auth()->user()->is_super_admin) {
                return $this->forbiddenResponse('Only super administrators can modify super administrator status');
            }

            $rules = [
                'name' => 'sometimes|string|max:255',
                'username' => [
                    'sometimes',
                    'string',
                    'max:100',
                    Rule::unique('admin_users')->ignore($user->id),
                ],
                'email' => [
                    'sometimes',
                    'email',
                    'max:255',
                    Rule::unique('admin_users')->ignore($user->id),
                ],
                'password' => 'sometimes|string|min:8|confirmed',
                'status' => 'sometimes|in:active,inactive,suspended',
                'is_super_admin' => 'sometimes|boolean',
                'permissions' => 'sometimes|array',
                'permissions.*' => 'string',
                'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];

            $data = $this->validateRequest($request, $rules);

            // 处理头像更新
            if ($request->hasFile('avatar')) {
                // 删除旧头像
                if ($user->avatar && Storage::disk('public')->exists(str_replace('storage/', '', $user->avatar))) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $user->avatar));
                }

                $avatar = $request->file('avatar');
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $avatar->getClientOriginalExtension();
                $avatarPath = $avatar->storeAs('avatars', $filename, 'public');
                $data['avatar'] = 'storage/' . $avatarPath;
            }

            // 处理密码更新
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            // 更新权限
            if (isset($data['permissions'])) {
                $user->syncPermissions($data['permissions']);
            }

            do_action('user.updated', $user, $request);

            return $this->successResponse([
                'user' => $this->transformResource($user),
                'permissions' => $user->getPermissionNames(),
            ], 'User updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int $userId): JsonResponse
    {
        try {
            $user = AdminUser::find($userId);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // 防止用户删除自己的账户
            if ($userId == auth()->id()) {
                return $this->forbiddenResponse('You cannot delete your own account');
            }

            // 防止删除唯一的超级管理员
            if ($user->is_super_admin) {
                $superAdminCount = AdminUser::where('is_super_admin', true)->count();
                if ($superAdminCount <= 1) {
                    return $this->forbiddenResponse('Cannot delete the only super administrator');
                }
            }

            // 删除用户头像
            if ($user->avatar && Storage::disk('public')->exists(str_replace('storage/', '', $user->avatar))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $user->avatar));
            }

            // 撤销所有API令牌
            $user->tokens()->delete();

            do_action('user.deleting', $user);

            $user->delete();

            do_action('user.deleted', $userId);

            return $this->successResponse(null, 'User deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    public function resetPassword(Request $request, int $userId): JsonResponse
    {
        try {
            $user = AdminUser::find($userId);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            $data = $this->validateRequest($request, [
                'password' => 'required|string|min:8|confirmed',
                'revoke_tokens' => 'sometimes|boolean',
            ]);

            // 更新密码
            $user->update([
                'password' => Hash::make($data['password'])
            ]);

            // 可选择撤销所有现有令牌
            if ($data['revoke_tokens'] ?? false) {
                $user->tokens()->delete();
            }

            do_action('user.password_reset', $user, $request);

            return $this->successResponse([
                'user_id' => $user->id,
                'tokens_revoked' => $data['revoke_tokens'] ?? false,
            ], 'Password reset successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    public function updatePermissions(Request $request, int $userId): JsonResponse
    {
        try {
            $user = AdminUser::find($userId);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // 只有超级管理员才能修改权限
            if (!auth()->user()->is_super_admin) {
                return $this->forbiddenResponse('Only super administrators can modify user permissions');
            }

            // 超级管理员拥有所有权限，不需要单独设置
            if ($user->is_super_admin) {
                return $this->errorResponse('Super administrators have all permissions by default', 400);
            }

            $data = $this->validateRequest($request, [
                'permissions' => 'required|array',
                'permissions.*' => 'string',
            ]);

            $user->syncPermissions($data['permissions']);

            do_action('user.permissions_updated', $user, $data['permissions'], $request);

            return $this->successResponse([
                'user_id' => $user->id,
                'permissions' => $user->getPermissionNames(),
            ], 'User permissions updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user permissions: ' . $e->getMessage(), 500);
        }
    }

    public function getUserTokens(int $userId): JsonResponse
    {
        try {
            $user = AdminUser::find($userId);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // 只有超级管理员或用户本人才能查看令牌
            if (!auth()->user()->is_super_admin && auth()->id() !== $userId) {
                return $this->forbiddenResponse('You can only view your own tokens');
            }

            $tokens = $user->tokens()->select(['id', 'name', 'created_at', 'last_used_at'])->get();

            return $this->successResponse([
                'user_id' => $user->id,
                'tokens' => $tokens,
                'total_tokens' => $tokens->count(),
            ], 'User tokens retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user tokens: ' . $e->getMessage(), 500);
        }
    }

    public function revokeUserTokens(int $userId): JsonResponse
    {
        try {
            $user = AdminUser::find($userId);

            if (!$user) {
                return $this->notFoundResponse('User');
            }

            // 只有超级管理员才能撤销其他用户的令牌
            if (!auth()->user()->is_super_admin && auth()->id() !== $userId) {
                return $this->forbiddenResponse('You can only revoke your own tokens');
            }

            $tokenCount = $user->tokens()->count();
            $user->tokens()->delete();

            do_action('user.tokens_revoked', $user, $tokenCount);

            return $this->successResponse([
                'user_id' => $user->id,
                'revoked_tokens_count' => $tokenCount,
            ], 'All user tokens revoked successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to revoke user tokens: ' . $e->getMessage(), 500);
        }
    }
}