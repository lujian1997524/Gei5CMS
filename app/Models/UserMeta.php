<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMeta extends Model
{
    protected $table = 'user_meta';

    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value',
        'meta_type',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * 关联到用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取格式化的元数据值
     */
    public function getFormattedValueAttribute()
    {
        return $this->castValue($this->meta_value, $this->meta_type);
    }

    /**
     * 设置元数据值
     */
    public function setMetaValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['meta_value'] = json_encode($value);
            $this->attributes['meta_type'] = 'json';
        } elseif (is_bool($value)) {
            $this->attributes['meta_value'] = $value ? '1' : '0';
            $this->attributes['meta_type'] = 'boolean';
        } elseif (is_numeric($value)) {
            $this->attributes['meta_value'] = (string) $value;
            $this->attributes['meta_type'] = 'number';
        } else {
            $this->attributes['meta_value'] = (string) $value;
            $this->attributes['meta_type'] = 'string';
        }
    }

    /**
     * 根据类型转换值
     */
    protected function castValue($value, $type)
    {
        return match($type) {
            'boolean' => (bool) $value,
            'number' => is_numeric($value) ? (strpos($value, '.') !== false ? (float) $value : (int) $value) : $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * 作用域：根据键查询
     */
    public function scopeByKey($query, $key)
    {
        return $query->where('meta_key', $key);
    }

    /**
     * 作用域：根据用户查询
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}