<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PluginData extends BaseModel
{
    use HasFactory;

    protected $table = 'plugin_data';

    protected $fillable = [
        'plugin_slug',
        'data_key',
        'data_value',
    ];

    // 关联关系
    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'plugin_slug', 'slug');
    }

    // 作用域
    public function scopeByPlugin($query, $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('data_key', $key);
    }
}