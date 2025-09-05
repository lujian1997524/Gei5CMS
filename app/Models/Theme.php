<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Theme extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'version',
        'author',
        'website',
        'screenshot',
        'application_type',
        'status',
        'config',
        'table_schema',
        'required_plugins',
        'default_settings',
        'has_update',
        'available_version',
        'installed_at',
    ];

    protected $casts = [
        'config' => 'array',
        'table_schema' => 'array',
        'required_plugins' => 'array',
        'default_settings' => 'array',
        'has_update' => 'boolean',
        'installed_at' => 'datetime',
    ];

    // 关联关系
    public function themeCustomizer()
    {
        return $this->hasMany(ThemeCustomizer::class, 'theme_slug', 'slug');
    }

    // 作用域
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByApplicationType($query, $type)
    {
        return $query->where('application_type', $type);
    }
}