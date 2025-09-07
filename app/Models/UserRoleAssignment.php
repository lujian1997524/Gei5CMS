<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRoleAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'role_id', 
        'assigned_at',
        'assigned_by',
        'expires_at',
        'role_meta',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'role_meta' => 'array',
        'user_id' => 'integer',
        'role_id' => 'integer',
        'assigned_by' => 'integer',
    ];

    /**
     * 关联到用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联到角色
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    /**
     * 关联到分配者（管理员）
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_by');
    }

    /**
     * 检查分配是否已过期
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * 检查分配是否仍然有效
     */
    public function isActive(): bool
    {
        return !$this->isExpired() && $this->role && $this->role->is_active;
    }

    /**
     * 作用域：有效的分配
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * 作用域：已过期的分配
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * 作用域：特定用户的分配
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：特定角色的分配
     */
    public function scopeForRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }
}