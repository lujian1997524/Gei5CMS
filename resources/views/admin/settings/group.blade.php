@extends('admin.layouts.app')

@section('title', $groupLabel . ' - 系统设置')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">系统设置</a></li>
<li class="breadcrumb-item active">{{ $groupLabel }}</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">{{ $groupLabel }}</h1>
            <p class="page-description">{{ $groupDescription }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2" style="font-size: 14px;"></i>
                返回设置
            </a>
            <a href="{{ route('admin.settings.create') }}?group={{ $group }}" class="btn btn-primary">
                <i class="bi bi-plus me-2" style="font-size: 14px;"></i>
                添加配置
            </a>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-alert-triangle me-2"></i>
    <div>
        <strong>请检查以下问题：</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Settings Form -->
<form action="{{ route('admin.settings.update-group', $group) }}" method="POST" id="settingsForm">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">配置选项</h5>
                    <div class="card-actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-floppy me-1"></i>
                            保存更改
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($settings as $setting)
                    <div class="setting-item mb-4 pb-4 border-bottom">
                        <div class="row align-items-start">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    {{ ucwords(str_replace('_', ' ', $setting->setting_key)) }}
                                    @if($setting->is_autoload)
                                        <span class="badge bg-success ms-2" style="font-size: 10px;">自动加载</span>
                                    @endif
                                </label>
                                @if($setting->description)
                                    <small class="text-muted d-block">{{ $setting->description }}</small>
                                @endif
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <strong>键名:</strong> {{ $setting->setting_key }}<br>
                                        <strong>类型:</strong> {{ $setting->setting_type }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if($setting->setting_type === 'boolean')
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="setting_{{ $setting->id }}"
                                               name="settings[{{ $setting->setting_key }}]"
                                               value="1"
                                               @if($setting->setting_value) checked @endif>
                                        <label class="form-check-label" for="setting_{{ $setting->id }}">
                                            {{ $setting->setting_value ? '已启用' : '已禁用' }}
                                        </label>
                                    </div>
                                @elseif($setting->setting_type === 'text')
                                    <textarea class="form-control" 
                                              name="settings[{{ $setting->setting_key }}]"
                                              rows="3" 
                                              placeholder="请输入{{ $setting->description ?? $setting->setting_key }}">{{ $setting->setting_value }}</textarea>
                                @elseif($setting->setting_type === 'integer')
                                    <input type="number" 
                                           class="form-control" 
                                           name="settings[{{ $setting->setting_key }}]"
                                           value="{{ $setting->setting_value }}"
                                           placeholder="请输入数字">
                                @elseif($setting->setting_type === 'json')
                                    <textarea class="form-control font-monospace" 
                                              name="settings[{{ $setting->setting_key }}]"
                                              rows="4" 
                                              placeholder="请输入有效的JSON格式">{{ is_string($setting->setting_value) ? $setting->setting_value : json_encode($setting->setting_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                @else
                                    <input type="text" 
                                           class="form-control" 
                                           name="settings[{{ $setting->setting_key }}]"
                                           value="{{ $setting->setting_value }}"
                                           placeholder="请输入{{ $setting->description ?? $setting->setting_key }}">
                                @endif
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.settings.edit', $setting) }}">
                                                <i class="bi bi-edit me-2"></i>编辑
                                            </a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-warning" onclick="resetSetting({{ $setting->id }})">
                                                <i class="bi bi-arrow-clockwise me-2"></i>重置
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.settings.destroy', $setting) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('确定要删除这个设置吗？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>删除
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-gear" style="font-size: 48px; color: var(--text-muted);"></i>
                        </div>
                        <h5>此分组暂无配置项</h5>
                        <p class="text-muted mb-4">点击右上角的"添加配置"按钮来创建第一个配置项</p>
                        <a href="{{ route('admin.settings.create') }}?group={{ $group }}" class="btn btn-primary">
                            <i class="bi bi-plus me-2"></i>
                            添加第一个配置
                        </a>
                    </div>
                    @endforelse
                </div>
                @if($settings->count() > 0)
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">共 {{ $settings->count() }} 个配置项</small>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise me-1"></i>重置
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-floppy me-1"></i>保存更改
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <!-- Group Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title">分组信息</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="group-icon me-3">
                            <i class="{{ $group === 'general' ? 'bi bi-gear' : 'bi bi-folder' }}"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $groupLabel }}</div>
                            <small class="text-muted">{{ $group }}</small>
                        </div>
                    </div>
                    <p class="text-muted small">{{ $groupDescription }}</p>
                    
                    <div class="group-stats mt-3">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-value">{{ $settings->count() }}</div>
                                    <div class="stat-label">配置项</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-value">{{ $settings->where('is_autoload', true)->count() }}</div>
                                    <div class="stat-label">自动加载</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-value">{{ $settings->groupBy('setting_type')->count() }}</div>
                                    <div class="stat-label">数据类型</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">快捷操作</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.settings.create') }}?group={{ $group }}" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus me-2"></i>添加新配置
                        </a>
                        <button class="btn btn-outline-warning btn-sm" onclick="exportSettings()">
                            <i class="bi bi-download me-2"></i>导出配置
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="previewChanges()">
                            <i class="bi bi-eye me-2"></i>预览变更
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('styles')
<style>
    .setting-item:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    
    .group-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
    }
    
    .stat-item .stat-value {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-blue);
    }
    
    .stat-item .stat-label {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .form-check-input:checked {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }
    
    .font-monospace {
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
        font-size: 13px;
    }
</style>
@endpush

@push('scripts')
<script>
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    // 允许正常提交表单到服务器
    // 移除preventDefault以允许表单正常提交
    console.log('正在提交设置更新...');
});

function resetSetting(settingId) {
    if (confirm('确定要重置这个设置到默认值吗？')) {
        // 实现重置功能
        alert('重置功能开发中...');
    }
}

function resetForm() {
    if (confirm('确定要重置所有更改吗？')) {
        location.reload();
    }
}

function exportSettings() {
    alert('导出功能开发中...');
}

function previewChanges() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    const settings = {};
    
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('settings[')) {
            const settingKey = key.match(/settings\[(.+)\]/)[1];
            settings[settingKey] = value;
        }
    }
    
    let preview = '即将更新的设置：\n\n';
    for (const [key, value] of Object.entries(settings)) {
        preview += `${key}: ${value}\n`;
    }
    
    alert(preview || '没有需要更新的设置');
}
</script>
@endpush
@endsection