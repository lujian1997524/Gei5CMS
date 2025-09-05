<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
        'is_super_admin',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
    ];

    /**
     * 获取用户权限
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'admin_user_permissions', 'admin_user_id', 'permission_id');
    }

    /**
     * 检查用户是否有特定权限
     */
    public function hasPermission(string $permission): bool
    {
        // 超级管理员拥有所有权限
        if ($this->is_super_admin) {
            return true;
        }

        // 检查直接权限
        return $this->permissions()->where('permission_slug', $permission)->exists();
    }

    /**
     * 授予权限
     */
    public function givePermission(string $permission): void
    {
        $permissionModel = Permission::where('permission_slug', $permission)->first();
        if (!$permissionModel) {
            $permissionModel = Permission::create([
                'permission_name' => $permission,
                'permission_slug' => $permission,
                'group_name' => 'custom',
                'is_system' => false,
            ]);
        }
        $this->permissions()->syncWithoutDetaching([$permissionModel->id]);
    }

    /**
     * 撤销权限
     */
    public function revokePermission(string $permission): void
    {
        $permissionModel = Permission::where('permission_slug', $permission)->first();
        if ($permissionModel) {
            $this->permissions()->detach($permissionModel->id);
        }
    }

    /**
     * 同步权限
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = [];
        foreach ($permissions as $permission) {
            $permissionModel = Permission::where('permission_slug', $permission)->first();
            if (!$permissionModel) {
                $permissionModel = Permission::create([
                    'permission_name' => $permission,
                    'permission_slug' => $permission,
                    'group_name' => 'custom',
                    'is_system' => false,
                ]);
            }
            $permissionIds[] = $permissionModel->id;
        }
        $this->permissions()->sync($permissionIds);
    }

    /**
     * 获取所有权限名称
     */
    public function getPermissionNames(): array
    {
        return $this->permissions()->pluck('permission_slug')->toArray();
    }

    /**
     * 检查是否为活跃用户
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 更新最后登录信息
     */
    public function updateLastLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * 获取头像URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset($this->avatar);
        }
        
        // 使用用户名首字母作为默认头像
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * 作用域：活跃用户
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 作用域：超级管理员
     */
    public function scopeSuperAdmin($query)
    {
        return $query->where('is_super_admin', true);
    }
}