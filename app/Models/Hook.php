<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hook extends BaseModel
{
    use HasFactory;

    protected $table = 'hooks';

    protected $fillable = [
        'tag',
        'callback',
        'priority',
        'plugin_slug',
        'hook_type',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    // 关联关系
    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'plugin_slug', 'slug');
    }

    // 作用域
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTag($query, $tag)
    {
        return $query->where('tag', $tag);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('hook_type', $type);
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority');
    }
}