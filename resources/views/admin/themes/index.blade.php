@extends('admin.layouts.app')

@section('title', '主题管理')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">主题管理</h1>
            <p class="page-description">管理和配置网站主题外观</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="ti ti-refresh me-2" style="font-size: 14px;"></i>
                刷新列表
            </button>
            <a href="{{ route('admin.themes.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-2" style="font-size: 14px;"></i>
                上传主题
            </a>
        </div>
    </div>
</div>

<!-- Alerts -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-2"></i>
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-x me-2"></i>
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Themes Grid -->
@if(empty($availableThemes))
<div class="text-center py-5">
    <div class="mb-3">
        <i class="ti ti-palette" style="font-size: 64px; color: var(--text-secondary);"></i>
    </div>
    <h3 class="mb-2">暂无主题</h3>
    <p class="text-muted mb-4">还没有安装任何主题，点击上传主题开始打造网站外观</p>
    <a href="{{ route('admin.themes.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-2"></i>
        上传第一个主题
    </a>
</div>
@else
<div class="themes-grid">
    @foreach($availableThemes as $theme)
    <div class="theme-card {{ $theme['status'] === 'active' ? 'active' : '' }}">
        <!-- Theme Preview -->
        <div class="theme-preview">
            @if(!empty($theme['screenshots']))
                <img src="{{ $theme['screenshots'][0] }}" alt="{{ $theme['name'] }}" 
                     class="preview-image" loading="lazy">
            @else
                <div class="preview-placeholder">
                    <i class="ti ti-photo"></i>
                    <span>暂无预览</span>
                </div>
            @endif
            
            <!-- Active Badge -->
            @if($theme['status'] === 'active')
            <div class="active-badge">
                <i class="ti ti-check"></i>
                当前主题
            </div>
            @endif
            
            <!-- Quick Actions Overlay -->
            <div class="preview-overlay">
                <div class="preview-actions">
                    <a href="{{ route('admin.themes.show', $theme['slug']) }}" 
                       class="btn btn-sm btn-light" title="查看详情">
                        <i class="ti ti-eye"></i>
                    </a>
                    @if($theme['status'] !== 'active')
                    <a href="{{ route('admin.themes.preview', $theme['slug']) }}" 
                       class="btn btn-sm btn-light" title="预览主题">
                        <i class="ti ti-external-link"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Theme Info -->
        <div class="theme-info">
            <div class="theme-header">
                <h5 class="theme-name">{{ $theme['name'] }}</h5>
                <span class="theme-version">v{{ $theme['version'] }}</span>
            </div>
            
            <p class="theme-description">
                {{ Str::limit($theme['description'] ?: '暂无描述', 80) }}
            </p>
            
            @if($theme['author'])
            <div class="theme-meta">
                <span class="meta-item">
                    <i class="ti ti-user"></i>
                    {{ is_array($theme['author']) ? ($theme['author']['name'] ?? '未知') : $theme['author'] }}
                </span>
            </div>
            @endif

            <!-- Tags -->
            @if(!empty($theme['tags']))
            <div class="theme-tags">
                @foreach(array_slice($theme['tags'], 0, 3) as $tag)
                <span class="tag">{{ $tag }}</span>
                @endforeach
                @if(count($theme['tags']) > 3)
                <span class="tag">+{{ count($theme['tags']) - 3 }}</span>
                @endif
            </div>
            @endif
        </div>

        <!-- Theme Actions -->
        <div class="theme-actions">
            @if($theme['status'] === 'active')
            <div class="d-flex gap-2 w-100">
                <a href="{{ route('admin.themes.customize', $theme['slug']) }}" 
                   class="btn btn-primary btn-sm flex-fill">
                    <i class="ti ti-settings"></i>
                    自定义
                </a>
                <form action="{{ route('admin.themes.deactivate', $theme['slug']) }}" 
                      method="POST" class="flex-fill">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100"
                            onclick="return confirm('确定要停用此主题吗？')">
                        <i class="ti ti-pause"></i>
                        停用
                    </button>
                </form>
            </div>
            @else
            <div class="d-flex gap-2 w-100">
                <form action="{{ route('admin.themes.activate', $theme['slug']) }}" 
                      method="POST" class="flex-fill">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="ti ti-check"></i>
                        激活
                    </button>
                </form>
                
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                            data-bs-toggle="dropdown">
                        <i class="ti ti-dots"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" 
                               href="{{ route('admin.themes.show', $theme['slug']) }}">
                                <i class="ti ti-info-circle me-2"></i>
                                详情
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" 
                               href="{{ route('admin.themes.preview', $theme['slug']) }}">
                                <i class="ti ti-external-link me-2"></i>
                                预览
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('admin.themes.destroy', $theme['slug']) }}" 
                                  method="POST" 
                                  onsubmit="return confirm('确定要删除此主题吗？此操作不可恢复！')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="ti ti-trash me-2"></i>
                                    删除主题
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

<style>
/* Theme Cards */
.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.theme-card {
    background: var(--white);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition-fast);
    position: relative;
}

.theme-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-dropdown);
    border-color: var(--primary-blue);
}

.theme-card.active {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 1px var(--primary-green);
}

/* Theme Preview */
.theme-preview {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: var(--soft-gray);
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition-fast);
}

.theme-card:hover .preview-image {
    transform: scale(1.05);
}

.preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-secondary);
}

.preview-placeholder i {
    font-size: 48px;
    margin-bottom: 8px;
}

.preview-placeholder span {
    font-size: 12px;
}

.active-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--primary-green);
    color: white;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.preview-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: var(--transition-fast);
}

.theme-card:hover .preview-overlay {
    opacity: 1;
}

.preview-actions {
    display: flex;
    gap: 8px;
}

/* Theme Info */
.theme-info {
    padding: 20px;
}

.theme-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.theme-name {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.2;
}

.theme-version {
    font-size: 11px;
    color: var(--text-secondary);
    background: var(--soft-gray);
    padding: 3px 6px;
    border-radius: 4px;
    flex-shrink: 0;
}

.theme-description {
    color: var(--text-secondary);
    font-size: 14px;
    line-height: 1.4;
    margin: 0 0 12px 0;
}

.theme-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--text-secondary);
}

.meta-item i {
    font-size: 14px;
}

.theme-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 8px;
}

.tag {
    background: var(--light-blue);
    color: var(--primary-blue);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 500;
}

/* Theme Actions */
.theme-actions {
    padding: 16px 20px;
    border-top: 1px solid var(--border-color);
    background: var(--soft-gray);
}

.theme-actions .btn {
    font-size: 12px;
    padding: 8px 12px;
}

@media (max-width: 768px) {
    .themes-grid {
        grid-template-columns: 1fr;
    }
    
    .theme-header {
        flex-direction: column;
        gap: 8px;
    }
    
    .theme-version {
        align-self: flex-start;
    }
}
</style>
@endsection