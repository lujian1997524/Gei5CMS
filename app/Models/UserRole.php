<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserRole extends Model
{
    protected $fillable = [
        'role_slug',
        'role_name',
        'role_description',
        'permissions',
        'theme_slug',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * 关联到用户（通过中间表）
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role_assignments')
            ->withPivot(['assigned_at', 'assigned_by', 'expires_at', 'role_meta'])
            ->withTimestamps();
    }

    /**
     * 角色分配记录
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class, 'role_id');
    }

    /**
     * 检查角色是否有特定权限
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        
        // 支持通配符权限
        foreach ($permissions as $perm) {
            if ($perm === '*' || $perm === $permission) {
                return true;
            }
            
            // 支持模块级通配符，如 'content.*'
            if (str_ends_with($perm, '.*')) {
                $module = str_replace('.*', '', $perm);
                if (str_starts_with($permission, $module . '.')) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * 添加权限
     */
    public function givePermission(string $permission): self
    {
        $permissions = $this->permissions ?? [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }
        
        return $this;
    }

    /**
     * 移除权限
     */
    public function revokePermission(string $permission): self
    {
        $permissions = $this->permissions ?? [];
        
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        
        $this->permissions = array_values($permissions);
        $this->save();
        
        return $this;
    }

    /**
     * 同步权限
     */
    public function syncPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        $this->save();
        
        return $this;
    }

    /**
     * 作用域：激活的角色
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：特定主题的角色
     */
    public function scopeByTheme($query, $themeSlug)
    {
        return $query->where('theme_slug', $themeSlug);
    }

    /**
     * 作用域：按优先级排序
     */
    public function scopeByPriority($query, $direction = 'desc')
    {
        return $query->orderBy('priority', $direction);
    }

    /**
     * 获取角色的用户数量
     */
    public function getUserCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * 检查角色是否属于特定主题
     */
    public function belongsToTheme(string $themeSlug): bool
    {
        return $this->theme_slug === $themeSlug;
    }

    /**
     * 检查角色是否已过期（如果设置了过期时间）
     */
    public function isExpiredForUser(User $user): bool
    {
        $assignment = $this->assignments()
            ->where('user_id', $user->id)
            ->first();
            
        if (!$assignment || !$assignment->expires_at) {
            return false;
        }
        
        return $assignment->expires_at->isPast();
    }

    /**
     * 为用户分配此角色
     */
    public function assignToUser(User $user, array $options = []): bool
    {
        $assignment = UserRoleAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $this->id,
            ],
            array_merge([
                'assigned_at' => now(),
                'assigned_by' => auth('admin')->id(),
            ], $options)
        );

        do_action('user.role.assigned', $user, $this, $assignment);

        return true;
    }

    /**
     * 从用户移除此角色
     */
    public function removeFromUser(User $user): bool
    {
        $deleted = UserRoleAssignment::where([
            'user_id' => $user->id,
            'role_id' => $this->id,
        ])->delete();

        if ($deleted) {
            do_action('user.role.removed', $user, $this);
        }

        return $deleted > 0;
    }
}