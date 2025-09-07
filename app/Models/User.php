<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 用户元数据关联
     */
    public function meta(): HasMany
    {
        return $this->hasMany(UserMeta::class);
    }

    /**
     * 用户角色关联
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(UserRole::class, 'user_role_assignments')
            ->withPivot(['assigned_at', 'assigned_by', 'expires_at', 'role_meta'])
            ->withTimestamps();
    }

    /**
     * 获取用户的有效角色（未过期的）
     */
    public function activeRoles(): BelongsToMany
    {
        return $this->roles()
            ->wherePivot(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where('user_roles.is_active', true);
    }

    /**
     * 获取用户元数据值
     */
    public function getMeta(string $key, $default = null)
    {
        $meta = $this->meta()->where('meta_key', $key)->first();
        
        if (!$meta) {
            return $default;
        }
        
        return $meta->formatted_value;
    }

    /**
     * 设置用户元数据值
     */
    public function setMeta(string $key, $value): bool
    {
        return $this->meta()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value]
        ) !== null;
    }

    /**
     * 删除用户元数据
     */
    public function deleteMeta(string $key): bool
    {
        return $this->meta()->where('meta_key', $key)->delete() > 0;
    }

    /**
     * 获取用户所有元数据
     */
    public function getAllMeta(): array
    {
        $result = [];
        
        foreach ($this->meta as $meta) {
            $result[$meta->meta_key] = $meta->formatted_value;
        }
        
        return $result;
    }

    /**
     * 批量设置元数据
     */
    public function syncMeta(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->setMeta($key, $value);
        }
    }

    /**
     * 检查用户是否有特定角色
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->activeRoles()
            ->where('user_roles.role_slug', $roleSlug)
            ->exists();
    }

    /**
     * 检查用户是否有任一角色
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->activeRoles()
            ->whereIn('user_roles.role_slug', $roleSlugs)
            ->exists();
    }

    /**
     * 检查用户是否有所有角色
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        $userRoles = $this->activeRoles()
            ->whereIn('user_roles.role_slug', $roleSlugs)
            ->pluck('user_roles.role_slug')
            ->toArray();
            
        return count($userRoles) === count($roleSlugs);
    }

    /**
     * 检查用户是否有特定权限
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->activeRoles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 获取用户所有权限
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        
        foreach ($this->activeRoles as $role) {
            $rolePermissions = $role->permissions ?? [];
            $permissions = array_merge($permissions, $rolePermissions);
        }
        
        return array_unique($permissions);
    }

    /**
     * 分配角色给用户
     */
    public function assignRole(string $roleSlug, array $options = []): bool
    {
        $role = UserRole::active()
            ->where('role_slug', $roleSlug)
            ->first();
            
        if (!$role) {
            return false;
        }
        
        return $role->assignToUser($this, $options);
    }

    /**
     * 移除用户角色
     */
    public function removeRole(string $roleSlug): bool
    {
        $role = UserRole::where('role_slug', $roleSlug)->first();
        
        if (!$role) {
            return false;
        }
        
        return $role->removeFromUser($this);
    }

    /**
     * 同步用户角色
     */
    public function syncRoles(array $roleSlugs): void
    {
        // 获取要分配的角色ID
        $roleIds = UserRole::active()
            ->whereIn('role_slug', $roleSlugs)
            ->pluck('id')
            ->toArray();
            
        // 同步角色关联，保留额外的数据
        $syncData = [];
        foreach ($roleIds as $roleId) {
            $syncData[$roleId] = [
                'assigned_at' => now(),
                'assigned_by' => auth('admin')->id(),
            ];
        }
        
        $this->roles()->sync($syncData);
        
        do_action('user.roles.synced', $this, $roleSlugs);
    }

    /**
     * 获取用户角色名称列表
     */
    public function getRoleNames(): array
    {
        return $this->activeRoles()->pluck('user_roles.role_name')->toArray();
    }

    /**
     * 获取用户角色标识符列表
     */
    public function getRoleSlugs(): array
    {
        return $this->activeRoles()->pluck('user_roles.role_slug')->toArray();
    }

    /**
     * 获取用户的最高优先级角色
     */
    public function getHighestPriorityRole(): ?UserRole
    {
        return $this->activeRoles()
            ->orderBy('user_roles.priority', 'desc')
            ->first();
    }

    /**
     * 检查用户是否属于特定主题的角色
     */
    public function hasRoleInTheme(string $themeSlug): bool
    {
        return $this->activeRoles()
            ->where('user_roles.theme_slug', $themeSlug)
            ->exists();
    }

    /**
     * 获取用户在特定主题中的角色
     */
    public function getRolesInTheme(string $themeSlug): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeRoles()
            ->where('user_roles.theme_slug', $themeSlug)
            ->get();
    }

    /**
     * 作用域：已验证邮箱的用户
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * 作用域：未验证邮箱的用户
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * 作用域：有特定角色的用户
     */
    public function scopeWithRole($query, $roleSlug)
    {
        return $query->whereHas('activeRoles', function ($q) use ($roleSlug) {
            $q->where('role_slug', $roleSlug);
        });
    }

    /**
     * 作用域：有特定元数据的用户
     */
    public function scopeWithMeta($query, $key, $value = null)
    {
        return $query->whereHas('meta', function ($q) use ($key, $value) {
            $q->where('meta_key', $key);
            if ($value !== null) {
                $q->where('meta_value', $value);
            }
        });
    }
}
