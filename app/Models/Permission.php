<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'permission_name',
        'permission_slug', 
        'description',
        'group_name',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * 关联管理员用户
     */
    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_permissions', 'permission_id', 'admin_user_id');
    }

    /**
     * 作用域：系统权限
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 作用域：自定义权限
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * 作用域：按组筛选
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group_name', $group);
    }

    /**
     * 获取所有权限组
     */
    public static function getAllGroups(): array
    {
        return self::distinct()->pluck('group_name')->filter()->toArray();
    }

    /**
     * 获取默认权限列表
     */
    public static function getDefaultPermissions(): array
    {
        return [
            // 插件管理
            ['name' => '查看插件', 'slug' => 'plugins.view', 'group' => '插件管理'],
            ['name' => '安装插件', 'slug' => 'plugins.create', 'group' => '插件管理'],
            ['name' => '编辑插件', 'slug' => 'plugins.edit', 'group' => '插件管理'],
            ['name' => '删除插件', 'slug' => 'plugins.delete', 'group' => '插件管理'],
            ['name' => '批量操作插件', 'slug' => 'plugins.bulk', 'group' => '插件管理'],

            // 主题管理
            ['name' => '查看主题', 'slug' => 'themes.view', 'group' => '主题管理'],
            ['name' => '安装主题', 'slug' => 'themes.create', 'group' => '主题管理'],
            ['name' => '编辑主题', 'slug' => 'themes.edit', 'group' => '主题管理'],
            ['name' => '删除主题', 'slug' => 'themes.delete', 'group' => '主题管理'],
            ['name' => '批量操作主题', 'slug' => 'themes.bulk', 'group' => '主题管理'],

            // 设置管理
            ['name' => '查看设置', 'slug' => 'settings.view', 'group' => '设置管理'],
            ['name' => '创建设置', 'slug' => 'settings.create', 'group' => '设置管理'],
            ['name' => '编辑设置', 'slug' => 'settings.edit', 'group' => '设置管理'],
            ['name' => '删除设置', 'slug' => 'settings.delete', 'group' => '设置管理'],
            ['name' => '批量操作设置', 'slug' => 'settings.bulk', 'group' => '设置管理'],

            // 用户管理
            ['name' => '查看用户', 'slug' => 'users.view', 'group' => '用户管理'],
            ['name' => '创建用户', 'slug' => 'users.create', 'group' => '用户管理'],
            ['name' => '编辑用户', 'slug' => 'users.edit', 'group' => '用户管理'],
            ['name' => '删除用户', 'slug' => 'users.delete', 'group' => '用户管理'],
            ['name' => '批量操作用户', 'slug' => 'users.bulk', 'group' => '用户管理'],
            ['name' => '管理用户权限', 'slug' => 'users.permissions', 'group' => '用户管理'],

            // 钩子管理
            ['name' => '查看钩子', 'slug' => 'hooks.view', 'group' => '钩子管理'],
            ['name' => '创建钩子', 'slug' => 'hooks.create', 'group' => '钩子管理'],
            ['name' => '编辑钩子', 'slug' => 'hooks.edit', 'group' => '钩子管理'],
            ['name' => '删除钩子', 'slug' => 'hooks.delete', 'group' => '钩子管理'],
            ['name' => '批量操作钩子', 'slug' => 'hooks.bulk', 'group' => '钩子管理'],

            // 媒体库
            ['name' => '查看媒体', 'slug' => 'media.view', 'group' => '媒体库'],
            ['name' => '上传媒体', 'slug' => 'media.upload', 'group' => '媒体库'],
            ['name' => '删除媒体', 'slug' => 'media.delete', 'group' => '媒体库'],

            // 数据分析
            ['name' => '查看分析', 'slug' => 'analytics.view', 'group' => '数据分析'],

            // 日志管理
            ['name' => '查看日志', 'slug' => 'logs.view', 'group' => '日志管理'],
            ['name' => '删除日志', 'slug' => 'logs.delete', 'group' => '日志管理'],

            // 系统工具
            ['name' => '查看工具', 'slug' => 'tools.view', 'group' => '系统工具'],
            ['name' => '缓存管理', 'slug' => 'tools.cache', 'group' => '系统工具'],
            ['name' => '系统优化', 'slug' => 'tools.optimize', 'group' => '系统工具'],
        ];
    }

    /**
     * 创建默认权限
     */
    public static function createDefaultPermissions(): void
    {
        $permissions = self::getDefaultPermissions();
        
        foreach ($permissions as $permission) {
            self::firstOrCreate(
                ['permission_slug' => $permission['slug']],
                [
                    'permission_name' => $permission['name'],
                    'description' => $permission['description'] ?? null,
                    'group_name' => $permission['group'],
                    'is_system' => true,
                ]
            );
        }
    }
}