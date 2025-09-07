<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserMeta;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 主题用户管理API服务
 * 
 * 为主题提供统一的用户管理接口，支持元数据和角色管理
 */
class ThemeUserService
{
    /**
     * 获取或创建用户角色
     */
    public function createRole(array $roleData): UserRole
    {
        $roleData = $this->validateRoleData($roleData);
        
        do_action('theme.user.role.creating', $roleData);
        
        $role = UserRole::updateOrCreate(
            [
                'role_slug' => $roleData['role_slug'],
                'theme_slug' => $roleData['theme_slug'] ?? null,
            ],
            $roleData
        );
        
        do_action('theme.user.role.created', $role);
        
        Log::info("User role created/updated: {$role->role_slug}");
        
        return $role;
    }

    /**
     * 获取主题的所有角色
     */
    public function getThemeRoles(string $themeSlug): \Illuminate\Database\Eloquent\Collection
    {
        return UserRole::active()
            ->byTheme($themeSlug)
            ->byPriority()
            ->get();
    }

    /**
     * 删除主题角色
     */
    public function deleteThemeRole(string $roleSlug, string $themeSlug = null): bool
    {
        $query = UserRole::where('role_slug', $roleSlug);
        
        if ($themeSlug) {
            $query->where('theme_slug', $themeSlug);
        }
        
        $role = $query->first();
        
        if (!$role) {
            return false;
        }
        
        do_action('theme.user.role.deleting', $role);
        
        // 先移除用户的角色分配
        $role->users()->detach();
        
        $deleted = $role->delete();
        
        if ($deleted) {
            do_action('theme.user.role.deleted', $role);
            Log::info("User role deleted: {$roleSlug}");
        }
        
        return $deleted;
    }

    /**
     * 为用户分配角色
     */
    public function assignRoleToUser(User $user, string $roleSlug, array $options = []): bool
    {
        do_action('theme.user.role.assigning', $user, $roleSlug, $options);
        
        $result = $user->assignRole($roleSlug, $options);
        
        if ($result) {
            do_action('theme.user.role.assigned', $user, $roleSlug);
            
            // 清除用户权限缓存
            $this->clearUserPermissionCache($user);
        }
        
        return $result;
    }

    /**
     * 移除用户角色
     */
    public function removeRoleFromUser(User $user, string $roleSlug): bool
    {
        do_action('theme.user.role.removing', $user, $roleSlug);
        
        $result = $user->removeRole($roleSlug);
        
        if ($result) {
            do_action('theme.user.role.removed', $user, $roleSlug);
            
            // 清除用户权限缓存
            $this->clearUserPermissionCache($user);
        }
        
        return $result;
    }

    /**
     * 批量设置用户元数据
     */
    public function setUserMeta(User $user, array $metaData): bool
    {
        do_action('theme.user.meta.updating', $user, $metaData);
        
        try {
            foreach ($metaData as $key => $value) {
                $user->setMeta($key, $value);
            }
            
            do_action('theme.user.meta.updated', $user, $metaData);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to set user meta: " . $e->getMessage(), [
                'user_id' => $user->id,
                'meta_data' => $metaData,
            ]);
            
            return false;
        }
    }

    /**
     * 获取用户元数据
     */
    public function getUserMeta(User $user, ?string $key = null)
    {
        if ($key) {
            return $user->getMeta($key);
        }
        
        return $user->getAllMeta();
    }

    /**
     * 检查用户权限
     */
    public function userCan(User $user, string $permission): bool
    {
        // 支持缓存
        $cacheKey = "user_permission_{$user->id}_{$permission}";
        
        return Cache::remember($cacheKey, 300, function () use ($user, $permission) {
            return $user->hasPermission($permission);
        });
    }

    /**
     * 获取用户在主题中的角色
     */
    public function getUserThemeRoles(User $user, string $themeSlug): array
    {
        return $user->getRolesInTheme($themeSlug)
            ->map(function ($role) {
                return [
                    'slug' => $role->role_slug,
                    'name' => $role->role_name,
                    'description' => $role->role_description,
                    'permissions' => $role->permissions ?? [],
                    'priority' => $role->priority,
                ];
            })
            ->toArray();
    }

    /**
     * 创建用户查询构建器（带权限过滤）
     */
    public function getUserQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query();
        
        // 角色过滤
        if (!empty($filters['roles'])) {
            $roles = is_array($filters['roles']) ? $filters['roles'] : [$filters['roles']];
            $query->whereHas('activeRoles', function ($q) use ($roles) {
                $q->whereIn('user_roles.role_slug', $roles);
            });
        }
        
        // 元数据过滤
        if (!empty($filters['meta'])) {
            foreach ($filters['meta'] as $key => $value) {
                $query->withMeta($key, $value);
            }
        }
        
        // 邮箱验证状态过滤
        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->verified();
            } else {
                $query->unverified();
            }
        }
        
        // 注册时间过滤
        if (!empty($filters['registered_after'])) {
            $query->where('created_at', '>=', $filters['registered_after']);
        }
        
        if (!empty($filters['registered_before'])) {
            $query->where('created_at', '<=', $filters['registered_before']);
        }
        
        // 搜索关键词
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    /**
     * 批量操作用户
     */
    public function bulkUserAction(array $userIds, string $action, array $params = []): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        $users = User::whereIn('id', $userIds)->get();
        
        do_action('theme.users.bulk_action.start', $users, $action, $params);
        
        foreach ($users as $user) {
            try {
                $success = match($action) {
                    'assign_role' => $this->assignRoleToUser($user, $params['role_slug'] ?? '', $params),
                    'remove_role' => $this->removeRoleFromUser($user, $params['role_slug'] ?? ''),
                    'set_meta' => $this->setUserMeta($user, $params['meta_data'] ?? []),
                    'verify_email' => $this->verifyUserEmail($user),
                    'unverify_email' => $this->unverifyUserEmail($user),
                    default => false,
                };
                
                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed action for user ID: {$user->id}";
                }
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error for user ID {$user->id}: " . $e->getMessage();
            }
        }
        
        do_action('theme.users.bulk_action.complete', $results, $action, $params);
        
        return $results;
    }

    /**
     * 验证用户邮箱
     */
    public function verifyUserEmail(User $user): bool
    {
        if ($user->email_verified_at) {
            return true; // 已经验证过了
        }
        
        $user->email_verified_at = now();
        $result = $user->save();
        
        if ($result) {
            do_action('theme.user.email.verified', $user);
        }
        
        return $result;
    }

    /**
     * 取消用户邮箱验证
     */
    public function unverifyUserEmail(User $user): bool
    {
        if (!$user->email_verified_at) {
            return true; // 已经是未验证状态
        }
        
        $user->email_verified_at = null;
        $result = $user->save();
        
        if ($result) {
            do_action('theme.user.email.unverified', $user);
        }
        
        return $result;
    }

    /**
     * 获取用户统计信息
     */
    public function getUserStats(array $filters = []): array
    {
        $baseQuery = $this->getUserQuery($filters);
        
        return [
            'total' => $baseQuery->count(),
            'verified' => $baseQuery->clone()->verified()->count(),
            'unverified' => $baseQuery->clone()->unverified()->count(),
            'registered_today' => $baseQuery->clone()->whereDate('created_at', today())->count(),
            'registered_this_week' => $baseQuery->clone()->where('created_at', '>=', now()->startOfWeek())->count(),
            'registered_this_month' => $baseQuery->clone()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * 清除用户权限缓存
     */
    protected function clearUserPermissionCache(User $user): void
    {
        $patterns = [
            "user_permission_{$user->id}_*",
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * 验证角色数据
     */
    protected function validateRoleData(array $data): array
    {
        $required = ['role_slug', 'role_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Role field '{$field}' is required");
            }
        }
        
        // 设置默认值
        return array_merge([
            'role_description' => '',
            'permissions' => [],
            'theme_slug' => null,
            'is_active' => true,
            'priority' => 0,
        ], $data);
    }

    /**
     * 注册主题钩子
     */
    public function registerThemeHooks(string $themeSlug): void
    {
        // 主题激活时清理角色
        add_action('theme.activated', function ($activatedTheme) use ($themeSlug) {
            if ($activatedTheme !== $themeSlug) {
                // 停用当前主题的角色
                UserRole::where('theme_slug', $themeSlug)
                    ->update(['is_active' => false]);
            }
        });
        
        // 主题停用时清理角色
        add_action('theme.deactivated', function ($deactivatedTheme) use ($themeSlug) {
            if ($deactivatedTheme === $themeSlug) {
                UserRole::where('theme_slug', $themeSlug)
                    ->update(['is_active' => false]);
            }
        });
    }
}