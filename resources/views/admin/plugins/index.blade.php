@extends('admin.layouts.app')

@section('title', '插件管理')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">插件管理</h1>
            <p class="page-description">管理和配置网站插件功能</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="ti ti-refresh me-2" style="font-size: 14px;"></i>
                刷新列表
            </button>
            <a href="{{ route('admin.plugins.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-2" style="font-size: 14px;"></i>
                上传插件
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

<!-- Plugins Grid -->
@if(empty($availablePlugins))
<div class="text-center py-5">
    <div class="mb-3">
        <i class="ti ti-puzzle" style="font-size: 64px; color: var(--text-secondary);"></i>
    </div>
    <h3 class="mb-2">暂无插件</h3>
    <p class="text-muted mb-4">还没有安装任何插件，点击上传插件开始使用功能扩展</p>
    <a href="{{ route('admin.plugins.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-2"></i>
        上传第一个插件
    </a>
</div>
@else
<div class="plugins-grid">
    @foreach($availablePlugins as $plugin)
    <div class="plugin-card">
        <div class="plugin-card-header">
            <div class="plugin-info">
                <div class="plugin-icon">
                    <i class="ti ti-puzzle"></i>
                </div>
                <div>
                    <h5 class="plugin-name">{{ $plugin['name'] }}</h5>
                    <span class="plugin-version">v{{ $plugin['version'] }}</span>
                </div>
            </div>
            <div class="plugin-status">
                @if($plugin['status'] === 'active')
                <span class="status-badge active">
                    <i class="ti ti-check"></i>
                    已激活
                </span>
                @else
                <span class="status-badge inactive">
                    <i class="ti ti-circle"></i>
                    未激活
                </span>
                @endif
            </div>
        </div>

        <div class="plugin-card-body">
            <p class="plugin-description">
                {{ $plugin['description'] ?: '暂无描述' }}
            </p>
            
            @if($plugin['author'])
            <div class="plugin-meta">
                <span class="meta-item">
                    <i class="ti ti-user"></i>
                    作者：{{ is_array($plugin['author']) ? ($plugin['author']['name'] ?? '未知') : $plugin['author'] }}
                </span>
            </div>
            @endif
        </div>

        <div class="plugin-card-footer">
            <div class="plugin-actions">
                @if($plugin['status'] === 'active')
                <form action="{{ route('admin.plugins.deactivate', $plugin['slug']) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm" 
                            onclick="return confirm('确定要停用此插件吗？')">
                        <i class="ti ti-pause"></i>
                        停用
                    </button>
                </form>
                @else
                <form action="{{ route('admin.plugins.activate', $plugin['slug']) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ti ti-play"></i>
                        激活
                    </button>
                </form>
                @endif

                <a href="{{ route('admin.plugins.show', $plugin['slug']) }}" class="btn btn-outline-primary btn-sm">
                    <i class="ti ti-info-circle"></i>
                    详情
                </a>

                @if($plugin['installed'])
                <div class="dropdown d-inline">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                            data-bs-toggle="dropdown">
                        <i class="ti ti-dots"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <form action="{{ route('admin.plugins.destroy', $plugin['slug']) }}" method="POST" 
                                  onsubmit="return confirm('确定要删除此插件吗？此操作不可恢复！')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="ti ti-trash me-2"></i>
                                    删除插件
                                </button>
                            </form>
                        </li>
                        @if($plugin['status'] === 'active')
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button class="dropdown-item" data-bs-toggle="modal" 
                                    data-bs-target="#priorityModal{{ $plugin['slug'] }}">
                                <i class="ti ti-sort-ascending me-2"></i>
                                设置优先级
                            </button>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Priority Modal -->
    @if($plugin['installed'] && $plugin['status'] === 'active')
    <div class="modal fade" id="priorityModal{{ $plugin['slug'] }}" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="{{ route('admin.plugins.priority', $plugin['slug']) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">设置优先级</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="priority{{ $plugin['slug'] }}" class="form-label">
                                优先级 (0-100)
                            </label>
                            <input type="number" class="form-control" 
                                   id="priority{{ $plugin['slug'] }}" 
                                   name="priority" 
                                   value="{{ $plugin['priority'] }}"
                                   min="0" max="100" required>
                            <div class="form-text">数值越小优先级越高</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>
@endif

<style>
/* Plugin Cards */
.plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.plugin-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition-fast);
}

.plugin-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-dropdown);
}

.plugin-card-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    padding: 20px 20px 0 20px;
}

.plugin-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.plugin-icon {
    width: 40px;
    height: 40px;
    background: var(--light-blue);
    color: var(--primary-blue);
    border-radius: var(--border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.plugin-name {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.2;
}

.plugin-version {
    font-size: 12px;
    color: var(--text-secondary);
    background: var(--soft-gray);
    padding: 2px 6px;
    border-radius: 4px;
}

.plugin-status {
    margin-left: auto;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background: var(--light-green);
    color: var(--primary-green);
}

.status-badge.inactive {
    background: var(--soft-gray);
    color: var(--text-secondary);
}

.plugin-card-body {
    padding: 16px 20px;
}

.plugin-description {
    color: var(--text-secondary);
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 12px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.plugin-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
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

.plugin-card-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border-color);
    background: var(--soft-gray);
}

.plugin-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.plugin-actions .btn {
    font-size: 12px;
    padding: 6px 12px;
}

@media (max-width: 768px) {
    .plugins-grid {
        grid-template-columns: 1fr;
    }
    
    .plugin-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .plugin-status {
        margin-left: 0;
    }
}
</style>
@endsection