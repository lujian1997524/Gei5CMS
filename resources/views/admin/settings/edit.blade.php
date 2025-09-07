@extends('admin.layouts.app')

@section('title', '编辑设置 - ' . $setting->setting_key)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">系统设置</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.settings.group', $setting->setting_group) }}">{{ $groups[$setting->setting_group] }}</a></li>
<li class="breadcrumb-item active">编辑设置</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">编辑设置</h1>
            <p class="page-description">修改配置参数</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.settings.group', $setting->setting_group) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2" style="font-size: 14px;"></i>
                返回分组
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form action="{{ route('admin.settings.update', $setting) }}" method="POST" class="needs-validation" novalidate>
            @csrf
            @method('PUT')
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">基本信息</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- 设置键名 -->
                        <div class="col-md-6">
                            <label for="setting_key" class="form-label">
                                设置键名 <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('setting_key') is-invalid @enderror" 
                                   id="setting_key" 
                                   name="setting_key" 
                                   value="{{ old('setting_key', $setting->setting_key) }}"
                                   placeholder="例如: site_name"
                                   required>
                            <div class="form-text">
                                使用小写字母和下划线，例如：site_name, max_upload_size
                            </div>
                            @error('setting_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 设置类型 -->
                        <div class="col-md-6">
                            <label for="setting_type" class="form-label">
                                数据类型 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('setting_type') is-invalid @enderror" 
                                    id="setting_type" 
                                    name="setting_type" 
                                    required>
                                <option value="">请选择数据类型</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" 
                                            @if(old('setting_type', $setting->setting_type) === $key) selected @endif>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('setting_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 设置分组 -->
                        <div class="col-md-6">
                            <label for="setting_group" class="form-label">
                                所属分组 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('setting_group') is-invalid @enderror" 
                                    id="setting_group" 
                                    name="setting_group" 
                                    required>
                                @foreach($groups as $key => $label)
                                    <option value="{{ $key }}" 
                                            @if(old('setting_group', $setting->setting_group) === $key) selected @endif>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('setting_group')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 自动加载 -->
                        <div class="col-md-6">
                            <label class="form-label">选项设置</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_autoload" 
                                       name="is_autoload" 
                                       value="1"
                                       @if(old('is_autoload', $setting->is_autoload)) checked @endif>
                                <label class="form-check-label" for="is_autoload">
                                    启用自动加载
                                </label>
                                <div class="form-text">
                                    启用后，此设置将在系统启动时自动加载以提高性能
                                </div>
                            </div>
                        </div>

                        <!-- 设置描述 -->
                        <div class="col-12">
                            <label for="description" class="form-label">设置描述</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="2" 
                                      placeholder="请输入设置的详细描述，帮助管理员理解此配置的作用">{{ old('description', $setting->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 设置值 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">当前值修改</h5>
                    <div class="card-subtitle text-muted mt-1">
                        最后更新：{{ $setting->updated_at->format('Y-m-d H:i:s') }}
                    </div>
                </div>
                <div class="card-body">
                    <!-- 字符串/文本 -->
                    <div id="value-string" class="value-input">
                        <label for="setting_value_string" class="form-label">设置值</label>
                        <input type="text" 
                               class="form-control" 
                               id="setting_value_string" 
                               name="setting_value" 
                               value="{{ $setting->setting_type === 'string' ? old('setting_value', $setting->setting_value) : '' }}"
                               placeholder="请输入设置值">
                    </div>

                    <!-- 长文本 -->
                    <div id="value-text" class="value-input" style="display: none;">
                        <label for="setting_value_text" class="form-label">设置值</label>
                        <textarea class="form-control" 
                                  id="setting_value_text" 
                                  name="setting_value_text" 
                                  rows="4" 
                                  placeholder="请输入长文本内容">{{ $setting->setting_type === 'text' ? old('setting_value', $setting->setting_value) : '' }}</textarea>
                    </div>

                    <!-- 整数 -->
                    <div id="value-integer" class="value-input" style="display: none;">
                        <label for="setting_value_integer" class="form-label">设置值</label>
                        <input type="number" 
                               class="form-control" 
                               id="setting_value_integer" 
                               name="setting_value_integer" 
                               value="{{ $setting->setting_type === 'integer' ? old('setting_value', $setting->setting_value) : '' }}"
                               placeholder="请输入整数值">
                    </div>

                    <!-- 布尔值 -->
                    <div id="value-boolean" class="value-input" style="display: none;">
                        <label class="form-label">设置值</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="setting_value_boolean" 
                                   name="setting_value_boolean" 
                                   value="1"
                                   @if($setting->setting_type === 'boolean' && $setting->setting_value) checked @endif>
                            <label class="form-check-label" for="setting_value_boolean">
                                启用此选项
                            </label>
                        </div>
                    </div>

                    <!-- JSON -->
                    <div id="value-json" class="value-input" style="display: none;">
                        <label for="setting_value_json" class="form-label">JSON 数据</label>
                        <textarea class="form-control font-monospace" 
                                  id="setting_value_json" 
                                  name="setting_value_json" 
                                  rows="6" 
                                  placeholder='{"key": "value"}'>{{ $setting->setting_type === 'json' ? old('setting_value', is_string($setting->setting_value) ? $setting->setting_value : json_encode(json_decode($setting->setting_value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) : '' }}</textarea>
                        <div class="form-text">
                            请输入有效的JSON格式数据
                        </div>
                    </div>
                </div>
            </div>

            <!-- 操作按钮 -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-floppy me-2"></i>
                    更新设置
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="previewChanges()">
                    <i class="bi bi-eye me-2"></i>
                    预览变更
                </button>
                <a href="{{ route('admin.settings.group', $setting->setting_group) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-2"></i>
                    取消
                </a>
            </div>
        </form>
    </div>

    <!-- 侧边栏信息 -->
    <div class="col-lg-4">
        <!-- 当前设置信息 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    设置信息
                </h6>
            </div>
            <div class="card-body">
                <div class="setting-info mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="setting-icon me-3">
                            <i class="bi bi-key"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">键名</div>
                            <small class="text-muted">{{ $setting->setting_key }}</small>
                        </div>
                    </div>
                </div>
                
                <div class="setting-info mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="setting-icon me-3">
                            <i class="bi bi-category"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">分组</div>
                            <small class="text-muted">{{ $groups[$setting->setting_group] }}</small>
                        </div>
                    </div>
                </div>

                <div class="setting-info mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="setting-icon me-3">
                            <i class="bi bi-database"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">数据类型</div>
                            <small class="text-muted">{{ $types[$setting->setting_type] }}</small>
                        </div>
                    </div>
                </div>

                <div class="setting-info mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="setting-icon me-3">
                            <i class="bi bi-bolt"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">自动加载</div>
                            <small class="text-muted">
                                @if($setting->is_autoload)
                                    <span class="badge bg-success">已启用</span>
                                @else
                                    <span class="badge bg-secondary">已禁用</span>
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                <div class="setting-timestamps mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>创建时间</span>
                        <span>{{ $setting->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>最后更新</span>
                        <span>{{ $setting->updated_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作历史 -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="bi bi-history me-2"></i>
                    操作提醒
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <div class="alert-content">
                        <div class="fw-semibold mb-1">修改须知</div>
                        <ul class="list-unstyled small mb-0">
                            <li>• 修改设置键名可能影响系统功能</li>
                            <li>• 更改数据类型将重置当前值</li>
                            <li>• 自动加载设置影响系统性能</li>
                            <li>• 建议在测试环境中先行验证</li>
                        </ul>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-outline-warning btn-sm" onclick="resetToDefault()">
                        <i class="bi bi-arrow-clockwise me-2"></i>重置为默认值
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="showHistory()">
                        <i class="bi bi-history me-2"></i>查看变更历史
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .setting-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: var(--soft-gray);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-blue);
        font-size: 16px;
    }
    
    .font-monospace {
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
        font-size: 13px;
    }
    
    .setting-timestamps {
        background: var(--soft-gray);
        border-radius: 8px;
        padding: 12px;
    }
    
    .alert-info {
        background: rgba(13, 110, 253, 0.08);
        border: 1px solid rgba(13, 110, 253, 0.2);
        color: var(--text-primary);
    }
</style>
@endpush

@push('scripts')
<script>
// 监听设置类型变化
document.getElementById('setting_type').addEventListener('change', function() {
    const type = this.value;
    
    // 隐藏所有值输入框
    document.querySelectorAll('.value-input').forEach(el => {
        el.style.display = 'none';
        el.querySelectorAll('input, textarea').forEach(input => {
            input.removeAttribute('name');
        });
    });
    
    // 显示对应的输入框
    if (type) {
        const targetEl = document.getElementById('value-' + type);
        if (targetEl) {
            targetEl.style.display = 'block';
            const input = targetEl.querySelector('input, textarea');
            if (input) {
                input.setAttribute('name', 'setting_value');
            }
        }
    }
});

function previewChanges() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    let preview = '设置变更预览:\n\n';
    preview += `键名: ${formData.get('setting_key')}\n`;
    preview += `类型: ${formData.get('setting_type')}\n`;
    preview += `分组: ${formData.get('setting_group')}\n`;
    preview += `描述: ${formData.get('description')}\n`;
    preview += `值: ${formData.get('setting_value') || '(空)'}\n`;
    preview += `自动加载: ${formData.get('is_autoload') ? '是' : '否'}`;
    
    alert(preview);
}

function resetToDefault() {
    if (confirm('确定要将此设置重置为默认值吗？此操作不可撤销。')) {
        alert('重置功能开发中...');
    }
}

function showHistory() {
    alert('变更历史功能开发中...');
}

// 初始化时显示当前设置类型对应的输入框
document.addEventListener('DOMContentLoaded', function() {
    const currentType = document.getElementById('setting_type').value;
    if (currentType) {
        const event = new Event('change');
        document.getElementById('setting_type').dispatchEvent(event);
    }
});
</script>
@endpush
@endsection