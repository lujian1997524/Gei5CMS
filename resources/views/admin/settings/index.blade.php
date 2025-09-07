@extends('admin.layouts.app')

@section('title', '系统设置')

@section('breadcrumb')
<li class="breadcrumb-item active">系统设置</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">系统设置</h1>
            <p class="page-description">管理网站的各项配置参数</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="exportSettings()">
                <i class="bi bi-download me-2" style="font-size: 14px;"></i>
                导出设置
            </button>
            <button class="btn btn-outline-info" onclick="document.getElementById('importFile').click()">
                <i class="bi bi-upload me-2" style="font-size: 14px;"></i>
                导入设置
            </button>
            <button class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise me-2" style="font-size: 14px;"></i>
                刷新
            </button>
            <a href="{{ route('admin.settings.create') }}" class="btn btn-primary">
                <i class="bi bi-plus me-2" style="font-size: 14px;"></i>
                新增设置
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

<!-- 隐藏文件输入 -->
<input type="file" id="importFile" accept=".json" style="display: none;" onchange="importSettings(this)">

<!-- 搜索栏 -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">搜索设置</label>
                <input type="text" id="settingsSearch" class="form-control" 
                       placeholder="输入设置名称、描述或键名进行搜索...">
            </div>
            <div class="col-md-3">
                <label class="form-label">按分组筛选</label>
                <select id="groupFilter" class="form-select">
                    <option value="">全部分组</option>
                    @foreach($allSettings as $group => $settings)
                    <option value="{{ $group }}">
                        @switch($group)
                            @case('general') 基础设置 @break
                            @case('performance') 性能设置 @break
                            @case('security') 安全设置 @break
                            @case('appearance') 外观设置 @break
                            @case('email') 邮件设置 @break
                            @case('seo') SEO设置 @break
                            @case('advanced') 系统维护 @break
                            @default {{ ucfirst($group) }}设置 @break
                        @endswitch
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                    <i class="bi bi-x me-2"></i>清除筛选
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Settings Content -->
<form id="settingsForm" method="POST" action="{{ route('admin.settings.bulk') }}">
    @csrf
    <input type="hidden" name="action" value="update_all">

    @foreach($allSettings as $group => $settings)
    <div class="card mb-4 setting-group" data-group="{{ $group }}">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi {{ $group === 'general' ? 'bi-gear' : ($group === 'performance' ? 'bi-activity' : ($group === 'security' ? 'bi-shield' : ($group === 'appearance' ? 'bi-brush' : ($group === 'email' ? 'bi-envelope' : ($group === 'seo' ? 'bi-search' : 'bi-folder'))))) }} me-2"></i>
                    @switch($group)
                        @case('general') 基础设置 @break
                        @case('performance') 性能设置 @break
                        @case('security') 安全设置 @break
                        @case('appearance') 外观设置 @break
                        @case('email') 邮件设置 @break
                        @case('seo') SEO设置 @break
                        @case('advanced') 系统维护 @break
                        @default {{ ucfirst($group) }}设置 @break
                    @endswitch
                </h5>
                <span class="badge bg-light text-dark group-count">{{ $settings->count() }} 项</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @foreach($settings as $setting)
                <div class="col-lg-6 setting-item-wrapper" 
                     data-key="{{ $setting->setting_key }}"
                     data-description="{{ $setting->description }}"
                     data-group="{{ $group }}">
                    <div class="setting-item">
                        <label class="form-label fw-semibold">
                            {{ $setting->description ?: ucwords(str_replace('_', ' ', $setting->setting_key)) }}
                            @if($setting->is_autoload)
                                <span class="badge bg-success ms-2" style="font-size: 10px;">重要</span>
                            @endif
                        </label>
                        
                        @if($setting->setting_type === 'boolean')
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="setting_{{ $setting->id }}"
                                       name="settings[{{ $setting->setting_key }}]"
                                       value="1"
                                       @if($setting->setting_value) checked @endif>
                                <label class="form-check-label text-muted" for="setting_{{ $setting->id }}">
                                    {{ $setting->setting_value ? '已启用' : '已禁用' }}
                                </label>
                            </div>
                        @elseif($setting->setting_type === 'text')
                            <textarea class="form-control mt-2" 
                                      name="settings[{{ $setting->setting_key }}]"
                                      rows="3"
                                      placeholder="{{ $setting->description }}">{{ $setting->setting_value }}</textarea>
                        @elseif($setting->setting_type === 'integer')
                            <div class="input-group mt-2">
                                <input type="number" 
                                       class="form-control" 
                                       name="settings[{{ $setting->setting_key }}]"
                                       value="{{ $setting->setting_value }}"
                                       placeholder="{{ $setting->description }}">
                                @if(str_contains($setting->setting_key, 'size'))
                                    <span class="input-group-text">字节</span>
                                @elseif(str_contains($setting->setting_key, 'timeout') || str_contains($setting->setting_key, 'ttl'))
                                    <span class="input-group-text">秒</span>
                                @elseif(str_contains($setting->setting_key, 'port'))
                                    <span class="input-group-text">端口</span>
                                @endif
                            </div>
                        @elseif($setting->setting_type === 'json')
                            <textarea class="form-control font-monospace mt-2" 
                                      name="settings[{{ $setting->setting_key }}]"
                                      rows="4" 
                                      placeholder="JSON格式数据">{{ is_string($setting->setting_value) ? $setting->setting_value : json_encode(json_decode($setting->setting_value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                        @else
                            <input type="text" 
                                   class="form-control mt-2" 
                                   name="settings[{{ $setting->setting_key }}]"
                                   value="{{ $setting->setting_value }}"
                                   placeholder="{{ $setting->description }}">
                        @endif
                        
                        @if($setting->description && strlen($setting->description) > 50)
                            <div class="form-text">{{ $setting->description }}</div>
                        @endif
                        
                        <!-- 操作按钮 -->
                        <div class="setting-actions mt-2">
                            <a href="{{ route('admin.settings.edit', $setting) }}" 
                               class="btn btn-outline-secondary btn-sm" title="编辑设置">
                                <i class="bi bi-edit"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach

    <!-- 批量操作按钮 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-text">
            <i class="bi bi-info-circle-fill me-1"></i>
            修改设置后请点击"保存所有设置"按钮使更改生效
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="resetAllSettings()">
                <i class="bi bi-arrow-clockwise me-2"></i>重置所有
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-floppy me-2"></i>保存所有设置
            </button>
        </div>
    </div>
</form>

@push('styles')
<style>
    .setting-item {
        background: var(--soft-gray);
        border-radius: var(--border-radius-sm);
        padding: 20px;
        position: relative;
        transition: var(--transition-fast);
    }
    
    .setting-item:hover {
        background: #EBEBF0;
    }
    
    .setting-actions {
        position: absolute;
        top: 8px;
        right: 8px;
        opacity: 0;
        transition: var(--transition-fast);
    }
    
    .setting-item:hover .setting-actions {
        opacity: 1;
    }
    
    .font-monospace {
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
        font-size: 13px;
    }
    
    .form-check-input:checked {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }
    
    .badge.bg-success {
        background-color: var(--primary-green) !important;
    }
    
    .input-group-text {
        background: var(--white);
        border-color: var(--border-color);
        color: var(--text-secondary);
        font-size: 13px;
    }
</style>
@endpush

@push('scripts')
<script>
// 搜索功能
document.getElementById('settingsSearch').addEventListener('input', function() {
    filterSettings();
});

document.getElementById('groupFilter').addEventListener('change', function() {
    filterSettings();
});

function filterSettings() {
    const searchTerm = document.getElementById('settingsSearch').value.toLowerCase();
    const selectedGroup = document.getElementById('groupFilter').value;
    
    const settingGroups = document.querySelectorAll('.setting-group');
    
    settingGroups.forEach(group => {
        const groupName = group.getAttribute('data-group');
        let hasVisibleItems = false;
        
        // 按分组筛选
        if (selectedGroup && selectedGroup !== groupName) {
            group.style.display = 'none';
            return;
        }
        
        const settingItems = group.querySelectorAll('.setting-item-wrapper');
        settingItems.forEach(item => {
            const key = item.getAttribute('data-key').toLowerCase();
            const description = item.getAttribute('data-description').toLowerCase();
            
            const matches = key.includes(searchTerm) || description.includes(searchTerm);
            
            if (matches) {
                item.style.display = 'block';
                hasVisibleItems = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        // 更新分组计数
        const visibleCount = group.querySelectorAll('.setting-item-wrapper[style*="display: block"], .setting-item-wrapper:not([style])').length;
        const countBadge = group.querySelector('.group-count');
        countBadge.textContent = `${visibleCount} 项`;
        
        // 显示/隐藏整个分组
        group.style.display = hasVisibleItems ? 'block' : 'none';
    });
}

function clearFilters() {
    document.getElementById('settingsSearch').value = '';
    document.getElementById('groupFilter').value = '';
    
    // 显示所有设置
    document.querySelectorAll('.setting-group').forEach(group => {
        group.style.display = 'block';
        
        // 显示所有设置项
        group.querySelectorAll('.setting-item-wrapper').forEach(item => {
            item.style.display = 'block';
        });
        
        // 恢复原始计数
        const totalCount = group.querySelectorAll('.setting-item-wrapper').length;
        const countBadge = group.querySelector('.group-count');
        countBadge.textContent = `${totalCount} 项`;
    });
}

// 导出设置
function exportSettings() {
    const formData = new FormData(document.getElementById('settingsForm'));
    const settings = {};
    
    for (const [key, value] of formData.entries()) {
        if (key.startsWith('settings[')) {
            const settingKey = key.slice(9, -1); // 移除 'settings[' 和 ']'
            settings[settingKey] = value;
        }
    }
    
    const exportData = {
        export_date: new Date().toISOString(),
        site_url: window.location.origin,
        settings: settings
    };
    
    const blob = new Blob([JSON.stringify(exportData, null, 2)], {
        type: 'application/json'
    });
    
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `gei5cms-settings-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showAlert('success', '设置已导出');
}

// 导入设置
function importSettings(fileInput) {
    const file = fileInput.files[0];
    if (!file) return;
    
    if (file.type !== 'application/json') {
        showAlert('error', '请选择JSON格式文件');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const importData = JSON.parse(e.target.result);
            
            if (!importData.settings) {
                showAlert('error', '无效的设置文件格式');
                return;
            }
            
            if (!confirm('确定要导入这些设置吗？这将覆盖当前的设置值！')) {
                return;
            }
            
            // 应用设置到表单
            let appliedCount = 0;
            for (const [key, value] of Object.entries(importData.settings)) {
                const inputs = document.querySelectorAll(`[name="settings[${key}]"]`);
                inputs.forEach(input => {
                    if (input.type === 'checkbox') {
                        input.checked = value === '1' || value === true;
                    } else {
                        input.value = value;
                    }
                    appliedCount++;
                });
            }
            
            showAlert('success', `已导入 ${appliedCount} 个设置，请点击"保存所有设置"应用更改`);
            
        } catch (error) {
            showAlert('error', '文件解析失败，请检查文件格式');
        }
    };
    
    reader.readAsText(file);
    
    // 清除文件输入
    fileInput.value = '';
}

// 原有的表单提交逻辑...
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // 显示加载状态
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat me-2" style="animation: spin 1s linear infinite;"></i>保存中...';
    submitBtn.disabled = true;
    
    // 提交表单
    const formData = new FormData(this);
    
    fetch('{{ route("admin.settings.bulk") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 显示成功消息
            showAlert('success', data.message || '设置保存成功');
        } else {
            showAlert('error', data.message || '设置保存失败');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', '网络错误，请重试');
    })
    .finally(() => {
        // 恢复按钮状态
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

function resetAllSettings() {
    if (confirm('确定要重置所有设置吗？此操作不可撤销！')) {
        window.location.reload();
    }
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="bi ${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // 插入到页面顶部
    const pageHeader = document.querySelector('.page-header');
    pageHeader.parentNode.insertBefore(alert, pageHeader.nextSibling);
    
    // 3秒后自动关闭
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// 添加旋转动画
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endpush
@endsection