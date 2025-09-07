@extends('admin.layouts.app')

@section('title', '添加管理员')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.admin-users.index') }}">管理员管理</a></li>
<li class="breadcrumb-item active">添加管理员</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">添加管理员</h1>
            <p class="page-description">创建新的管理员账户</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2" style="font-size: 14px;"></i>
                返回管理员列表
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form action="{{ route('admin.admin-users.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">基本信息</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- 姓名 -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                姓名 <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}"
                                   placeholder="请输入用户姓名"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 邮箱 -->
                        <div class="col-md-6">
                            <label for="email" class="form-label">
                                邮箱地址 <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}"
                                   placeholder="user@example.com"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 密码 -->
                        <div class="col-md-6">
                            <label for="password" class="form-label">
                                密码 <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password"
                                   placeholder="请输入密码（至少8位）"
                                   required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 确认密码 -->
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">
                                确认密码 <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation"
                                   placeholder="请再次输入密码"
                                   required>
                        </div>

                        <!-- 账户状态 -->
                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                账户状态 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status" 
                                    required>
                                <option value="active" @if(old('status', 'active') === 'active') selected @endif>
                                    活跃 - 可以正常登录
                                </option>
                                <option value="inactive" @if(old('status') === 'inactive') selected @endif>
                                    已停用 - 无法登录
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 超级管理员权限 -->
                        <div class="col-md-6">
                            <label class="form-label">权限设置</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_super_admin" 
                                       name="is_super_admin" 
                                       value="1"
                                       @if(old('is_super_admin')) checked @endif>
                                <label class="form-check-label" for="is_super_admin">
                                    <strong>超级管理员</strong>
                                    <small class="d-block text-muted">拥有所有系统权限，谨慎授予</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 操作按钮 -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-user-plus me-2"></i>
                    创建管理员
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="previewUser()">
                    <i class="bi bi-eye me-2"></i>
                    预览
                </button>
                <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-secondary">
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
                    <h6 class="help-title">账户安全</h6>
                    <ul class="list-unstyled small">
                        <li>• 密码至少8位字符</li>
                        <li>• 建议包含大小写字母和数字</li>
                        <li>• 避免使用常见密码</li>
                        <li>• 邮箱用于密码重置</li>
                    </ul>
                </div>
                
                <div class="help-section mb-4">
                    <h6 class="help-title">权限说明</h6>
                    <ul class="list-unstyled small">
                        <li><strong>普通管理员:</strong> 基础管理权限</li>
                        <li><strong>超级管理员:</strong> 所有系统权限</li>
                        <li>• 可以管理其他用户</li>
                        <li>• 可以修改系统设置</li>
                        <li>• 可以安装插件和主题</li>
                    </ul>
                </div>

                <div class="help-section">
                    <h6 class="help-title">最佳实践</h6>
                    <ul class="list-unstyled small">
                        <li>• 为每个管理员设置独立账户</li>
                        <li>• 不要共享管理员密码</li>
                        <li>• 定期检查用户活动日志</li>
                        <li>• 及时停用离职人员账户</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 权限预设模板 -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="bi bi-file-text me-2"></i>
                    常用权限模板
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="applyTemplate('super_admin')">
                        <i class="bi bi-star-fill me-2"></i>
                        超级管理员
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="applyTemplate('content_manager')">
                        <i class="bi bi-edit me-2"></i>
                        内容管理员
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="applyTemplate('system_operator')">
                        <i class="bi bi-gear me-2"></i>
                        系统操作员
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
    
    .form-check-input:checked {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }
    
    .alert-warning {
        background-color: rgba(255, 149, 0, 0.1);
        border: 1px solid rgba(255, 149, 0, 0.2);
        color: var(--text-primary);
    }
</style>
@endpush

@push('scripts')
<script>
function previewUser() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    let preview = '用户信息预览:\n\n';
    preview += `姓名: ${formData.get('name')}\n`;
    preview += `邮箱: ${formData.get('email')}\n`;
    preview += `状态: ${formData.get('status') === 'active' ? '活跃' : '已停用'}\n`;
    preview += `权限: ${formData.get('is_super_admin') ? '超级管理员' : '普通管理员'}`;
    
    alert(preview);
}

function applyTemplate(templateType) {
    const templates = {
        super_admin: {
            is_super_admin: true,
            status: 'active'
        },
        content_manager: {
            is_super_admin: false,
            status: 'active'
        },
        system_operator: {
            is_super_admin: false,
            status: 'active'
        }
    };
    
    const template = templates[templateType];
    if (template) {
        document.getElementById('is_super_admin').checked = template.is_super_admin;
        document.getElementById('status').value = template.status;
        
        alert(`已应用 ${templateType} 权限模板`);
    }
}

// 表单验证
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('两次输入的密码不一致，请重新输入');
        document.getElementById('password_confirmation').focus();
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('密码至少需要8位字符');
        document.getElementById('password').focus();
        return false;
    }
});
</script>
@endpush
@endsection