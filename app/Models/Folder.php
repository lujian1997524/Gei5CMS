<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Folder extends Model
{
    protected $table = 'gei5_folders';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'created_by',
        'sort_order',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function getPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    public function getFilesCountAttribute(): int
    {
        return $this->files()->count();
    }

    public function getSubfoldersCountAttribute(): int
    {
        return $this->children()->count();
    }

    public function getTotalSizeAttribute(): int
    {
        $size = $this->files()->sum('size');
        
        foreach ($this->children as $child) {
            $size += $child->total_size;
        }
        
        return $size;
    }

    public function getHumanTotalSizeAttribute(): string
    {
        $bytes = $this->total_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getAllFiles(): \Illuminate\Database\Eloquent\Collection
    {
        $files = $this->files;
        
        foreach ($this->children as $child) {
            $files = $files->merge($child->getAllFiles());
        }
        
        return $files;
    }

    public function getAllSubfolders(): \Illuminate\Database\Eloquent\Collection
    {
        $folders = $this->children;
        
        foreach ($this->children as $child) {
            $folders = $folders->merge($child->getAllSubfolders());
        }
        
        return $folders;
    }

    protected static function booted(): void
    {
        static::creating(function (Folder $folder) {
            if (empty($folder->slug)) {
                $folder->slug = Str::slug($folder->name);
            }
        });

        static::updating(function (Folder $folder) {
            if ($folder->isDirty('name') && empty($folder->slug)) {
                $folder->slug = Str::slug($folder->name);
            }
        });

        static::deleting(function (Folder $folder) {
            $folder->children()->delete();
            $folder->files()->delete();
        });
    }
}
