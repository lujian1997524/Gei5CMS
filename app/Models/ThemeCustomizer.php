<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThemeCustomizer extends BaseModel
{
    use HasFactory;

    protected $table = 'theme_customizer';

    protected $fillable = [
        'theme_slug',
        'setting_key',
        'setting_value',
    ];

    // 关联关系
    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_slug', 'slug');
    }

    // 作用域
    public function scopeByTheme($query, $themeSlug)
    {
        return $query->where('theme_slug', $themeSlug);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }
}