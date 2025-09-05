<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plugin extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'version',
        'author',
        'author_email',
        'website',
        'requires_php_version',
        'requires_cms_version',
        'status',
        'auto_update',
        'config',
        'dependencies',
        'service_type',
        'priority',
        'has_update',
        'available_version',
        'installed_at',
    ];

    protected $casts = [
        'config' => 'array',
        'dependencies' => 'array',
        'auto_update' => 'boolean',
        'has_update' => 'boolean',
        'priority' => 'integer',
        'installed_at' => 'datetime',
    ];

    // 关联关系
    public function hooks()
    {
        return $this->hasMany(Hook::class, 'plugin_slug', 'slug');
    }

    public function pluginData()
    {
        return $this->hasMany(PluginData::class, 'plugin_slug', 'slug');
    }

    // 作用域
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeBroken($query)
    {
        return $query->where('status', 'broken');
    }
}