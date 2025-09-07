@extends('admin.layouts.app')

@section('title', '新增系统设置')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">系统设置</a></li>
<li class="breadcrumb-item active">新增设置</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">新增系统设置</h1>
            <p class="page-description">创建新的配置参数</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2" style="font-size: 14px;"></i>
                返回设置
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form action="{{ route('admin.settings.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf
            
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
                                   value="{{ old('setting_key') }}"
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
                                            @if(old('setting_type') === $key) selected @endif>
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
                                            @if(old('setting_group', request('group')) === $key) selected @endif>
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
                                       @if(old('is_autoload', true)) checked @endif>
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
                                      placeholder="请输入设置的详细描述，帮助管理员理解此配置的作用">{{ old('description') }}</textarea>
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
                    <h5 class="card-title">默认值设置</h5>
                </div>
                <div class="card-body">
                    <!-- 字符串/文本 -->
                    <div id="value-string" class="value-input">
                        <label for="setting_value_string" class="form-label">设置值</label>
                        <input type="text" 
                               class="form-control" 
                               id="setting_value_string" 
                               name="setting_value" 
                               value="{{ old('setting_value') }}"
                               placeholder="请输入设置值">
                    </div>

                    <!-- 长文本 -->
                    <div id="value-text" class="value-input" style="display: none;">
                        <label for="setting_value_text" class="form-label">设置值</label>
                        <textarea class="form-control" 
                                  id="setting_value_text" 
                                  name="setting_value_text" 
                                  rows="4" 
                                  placeholder="请输入长文本内容">{{ old('setting_value') }}</textarea>
                    </div>

                    <!-- 整数 -->
                    <div id="value-integer" class="value-input" style="display: none;">
                        <label for="setting_value_integer" class="form-label">设置值</label>
                        <input type="number" 
                               class="form-control" 
                               id="setting_value_integer" 
                               name="setting_value_integer" 
                               value="{{ old('setting_value') }}"
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
                                   value="1">
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
                                  placeholder='{"key": "value"}'>{{ old('setting_value') }}</textarea>
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
                    创建设置
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="previewSetting()">
                    <i class="bi bi-eye me-2"></i>
                    预览
                </button>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-2"></i>
                    取消
                </a>
            </div>
        </form>
    </div>

    <!-- 侧边栏帮助 -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="bi bi-question-circle me-2"></i>
                    创建指南
                </h6>
            </div>
            <div class="card-body">
                <div class="help-section mb-4">
                    <h6 class="help-title">数据类型说明</h6>
                    <ul class="list-unstyled small">
                        <li><strong>字符串:</strong> 普通文本，如网站名称</li>
                        <li><strong>整数:</strong> 数字值，如最大上传大小</li>
                        <li><strong>布尔值:</strong> 开关选项，如启用/禁用</li>
                        <li><strong>长文本:</strong> 多行文本，如网站描述</li>
                        <li><strong>JSON:</strong> 结构化数据，如配置对象</li>
                    </ul>
                </div>
                
                <div class="help-section mb-4">
                    <h6 class="help-title">命名规范</h6>
                    <ul class="list-unstyled small">
                        <li>使用小写字母和下划线</li>
                        <li>具有描述性，如 <code>max_file_size</code></li>
                        <li>避免使用保留字</li>
                        <li>保持简洁明了</li>
                    </ul>
                </div>

                <div class="help-section">
                    <h6 class="help-title">分组说明</h6>
                    <ul class="list-unstyled small">
                        <li><strong>基本设置:</strong> 网站名称、描述等</li>
                        <li><strong>性能设置:</strong> 缓存、优化相关</li>
                        <li><strong>安全设置:</strong> 登录、权限控制</li>
                        <li><strong>其他分组:</strong> 根据功能分类</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 常用设置模板 -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="bi bi-file-text me-2"></i>
                    常用模板
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="loadTemplate('site_setting')">
                        <i class="bi bi-world me-2"></i>
                        网站配置模板
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="loadTemplate('performance_setting')">
                        <i class="bi bi-activity me-2"></i>
                        性能配置模板
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="loadTemplate('security_setting')">
                        <i class="bi bi-shield me-2"></i>
                        安全配置模板
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .help-title {
        color: var(--primary-blue);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .help-section {
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-light);
    }
    
    .help-section:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    code {
        background: var(--soft-gray);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .font-monospace {
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
        font-size: 13px;
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

// 加载模板
function loadTemplate(templateType) {
    const templates = {
        site_setting: {
            key: 'site_title',
            type: 'string',
            group: 'general',
            description: '网站标题，显示在浏览器标签页',
            value: '我的网站'
        },
        performance_setting: {
            key: 'cache_duration',
            type: 'integer',
            group: 'performance', 
            description: '缓存有效期（秒）',
            value: '3600'
        },
        security_setting: {
            key: 'enable_2fa',
            type: 'boolean',
            group: 'security',
            description: '启用双因素认证',
            value: false
        }
    };
    
    const template = templates[templateType];
    if (template) {
        document.getElementById('setting_key').value = template.key;
        document.getElementById('setting_type').value = template.type;
        document.getElementById('setting_group').value = template.group;
        document.getElementById('description').value = template.description;
        
        // 触发类型改变事件
        document.getElementById('setting_type').dispatchEvent(new Event('change'));
        
        // 设置对应的值
        setTimeout(() => {
            if (template.type === 'boolean') {
                document.getElementById('setting_value_boolean').checked = template.value;
            } else {
                const input = document.querySelector(`#value-${template.type} input, #value-${template.type} textarea`);
                if (input) {
                    input.value = template.value;
                }
            }
        }, 100);
    }
}

function previewSetting() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    let preview = '设置预览:\n\n';
    preview += `键名: ${formData.get('setting_key')}\n`;
    preview += `类型: ${formData.get('setting_type')}\n`;
    preview += `分组: ${formData.get('setting_group')}\n`;
    preview += `描述: ${formData.get('description')}\n`;
    preview += `值: ${formData.get('setting_value') || '(空)'}\n`;
    preview += `自动加载: ${formData.get('is_autoload') ? '是' : '否'}`;
    
    alert(preview);
}

// 初始化时触发一次类型改变
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('setting_type').dispatchEvent(new Event('change'));
});
</script>
@endpush
@endsection