<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiEndpoint extends BaseModel
{
    use HasFactory;

    protected $table = 'api_endpoints';

    protected $fillable = [
        'endpoint_path',
        'method',
        'controller',
        'action',
        'description',
        'parameters',
        'requires_auth',
        'permission_required',
        'is_active',
        'version',
    ];

    protected $casts = [
        'parameters' => 'array',
        'requires_auth' => 'boolean',
        'is_active' => 'boolean',
    ];

    // 作用域
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', strtoupper($method));
    }

    public function scopeByVersion($query, $version)
    {
        return $query->where('version', $version);
    }

    public function scopeRequiresAuth($query)
    {
        return $query->where('requires_auth', true);
    }
}