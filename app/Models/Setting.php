<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_group',
        'setting_type',
        'description',
        'is_autoload',
    ];

    protected $casts = [
        'is_autoload' => 'boolean',
    ];

    // 作用域
    public function scopeAutoload($query)
    {
        return $query->where('is_autoload', true);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('setting_group', $group);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    // 获取设置值
    public function getValueAttribute($value)
    {
        return match($this->setting_type) {
            'boolean' => (bool) $this->setting_value,
            'integer' => (int) $this->setting_value,
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }
}