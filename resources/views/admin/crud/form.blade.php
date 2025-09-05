@extends('admin.layouts.app')

@section('title', $title . ' - Gei5CMS')

@push('styles')
<style>
    .form-section {
        margin-bottom: 2rem;
    }
    
    .form-section h5 {
        color: #374151;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .form-actions {
        position: sticky;
        bottom: 0;
        background: white;
        border-top: 1px solid #e2e8f0;
        padding: 1rem 0;
        margin-top: 2rem;
    }
    
    .form-actions .btn {
        min-width: 120px;
    }
    
    .field-help {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    
    .field-error {
        font-size: 0.875rem;
        color: #dc2626;
        margin-top: 0.25rem;
    }
    
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        object-fit: cover;
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem;
    }
    
    .file-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .file-upload-area:hover {
        border-color: #3b82f6;
        background-color: #f8fafc;
    }
    
    .file-upload-area.dragover {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }
    
    .tag-input {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        min-height: 42px;
    }
    
    .tag-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        background: #3b82f6;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    
    .tag-remove {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0;
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $title }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.dashboard') }}">仪表盘</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.' . $route . '.index') }}">{{ str_replace(['创建 ', '编辑 '], '', $title) }}</a>
                </li>
                <li class="breadcrumb-item active">{{ $form_method === 'POST' ? '创建' : '编辑' }}</li>
            </ol>
        </nav>
    </div>
</div>

<form method="POST" action="{{ $form_action }}" enctype="multipart/form-data" id="adminForm">
    @csrf
    @if($form_method !== 'POST')
        @method($form_method)
    @endif
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    @foreach($fields as $field)
                        @if(!($field['tab'] ?? false) || ($field['tab'] ?? false) === 'main')
                            @include('admin.crud.fields.' . $field['type'], ['field' => $field])
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- 操作面板 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-settings me-2"></i>
                        操作
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            {{ $form_method === 'POST' ? '创建' : '更新' }}
                        </button>
                        
                        <button type="submit" name="save_and_continue" value="1" class="btn btn-outline-primary">
                            <i class="ti ti-check me-2"></i>
                            保存并继续编辑
                        </button>
                        
                        <a href="{{ route('admin.' . $route . '.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-x me-2"></i>
                            取消
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- 侧边栏字段 -->
            @foreach($fields as $field)
                @if(($field['tab'] ?? false) === 'sidebar')
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ $field['section_title'] ?? '设置' }}</h5>
                        </div>
                        <div class="card-body">
                            @include('admin.crud.fields.' . $field['type'], ['field' => $field])
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // 表单提交处理
    document.getElementById('adminForm').addEventListener('submit', function(e) {
        const submitButton = e.submitter;
        const originalText = submitButton.innerHTML;
        
        // 显示加载状态
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="ti ti-loader animate-spin me-2"></i>正在保存...';
        
        // 恢复按钮状态（如果提交失败）
        setTimeout(() => {
            if (submitButton.disabled) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        }, 10000);
    });

    // 文件上传预览
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
                document.getElementById(previewId).style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // 拖拽上传
    function setupDragAndDrop(areaId, inputId) {
        const area = document.getElementById(areaId);
        const input = document.getElementById(inputId);
        
        if (!area || !input) return;
        
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function() {
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                // 触发 change 事件
                input.dispatchEvent(new Event('change'));
            }
        });
        
        area.addEventListener('click', function() {
            input.click();
        });
    }

    // 标签输入功能
    function setupTagInput(inputId, hiddenInputId) {
        const container = document.getElementById(inputId);
        const hiddenInput = document.getElementById(hiddenInputId);
        
        if (!container || !hiddenInput) return;
        
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control border-0 flex-grow-1';
        input.placeholder = '输入标签后按回车...';
        input.style.minWidth = '200px';
        
        container.appendChild(input);
        
        function addTag(text) {
            if (!text.trim()) return;
            
            const tag = document.createElement('span');
            tag.className = 'tag-item';
            tag.innerHTML = `
                ${text.trim()}
                <button type="button" class="tag-remove">
                    <i class="ti ti-x" style="font-size: 12px;"></i>
                </button>
            `;
            
            tag.querySelector('.tag-remove').addEventListener('click', function() {
                tag.remove();
                updateHiddenInput();
            });
            
            container.insertBefore(tag, input);
            input.value = '';
            updateHiddenInput();
        }
        
        function updateHiddenInput() {
            const tags = Array.from(container.querySelectorAll('.tag-item'))
                .map(tag => tag.textContent.trim());
            hiddenInput.value = tags.join(',');
        }
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag(input.value);
            } else if (e.key === 'Backspace' && !input.value) {
                const lastTag = container.querySelector('.tag-item:last-of-type');
                if (lastTag) {
                    lastTag.remove();
                    updateHiddenInput();
                }
            }
        });
        
        // 初始化现有标签
        if (hiddenInput.value) {
            const initialTags = hiddenInput.value.split(',').filter(tag => tag.trim());
            initialTags.forEach(tag => addTag(tag));
        }
    }

    // 初始化所有交互功能
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化所有拖拽上传区域
        document.querySelectorAll('[data-upload-area]').forEach(area => {
            const inputId = area.getAttribute('data-upload-area');
            setupDragAndDrop(area.id, inputId);
        });
        
        // 初始化所有标签输入
        document.querySelectorAll('[data-tag-input]').forEach(container => {
            const hiddenInputId = container.getAttribute('data-tag-input');
            setupTagInput(container.id, hiddenInputId);
        });
        
        // 初始化 Select2
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    });
</script>
@endpush