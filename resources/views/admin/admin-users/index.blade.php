@extends('admin.layouts.app')

@section('title', '管理员管理')

@section('breadcrumb')
<li class="breadcrumb-item active">管理员管理</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">管理员管理</h1>
            <p class="page-description">管理系统管理员账户和权限</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise me-2" style="font-size: 14px;"></i>
                刷新
            </button>
            <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus me-2" style="font-size: 14px;"></i>
                添加管理员
            </a>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon blue">
                <i class="bi bi-people"></i>
            </div>
        </div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">总管理员数</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon green">
                <i class="bi bi-person-check"></i>
            </div>
        </div>
        <div class="stat-value">{{ $stats['active'] }}</div>
        <div class="stat-label">活跃管理员</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="color: #FF9500;">
                <i class="bi bi-person-dash"></i>
            </div>
        </div>
        <div class="stat-value">{{ $stats['inactive'] }}</div>
        <div class="stat-label">已停用</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="color: #5856D6;">
                <i class="bi bi-star-fill"></i>
            </div>
        </div>
        <div class="stat-value">{{ $stats['super_admins'] }}</div>
        <div class="stat-label">超级管理员</div>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">搜索管理员</label>
                <input type="text" name="search" class="form-control" 
                       value="{{ request('search') }}" 
                       placeholder="姓名或邮箱...">
            </div>
            <div class="col-md-3">
                <label class="form-label">状态筛选</label>
                <select name="status" class="form-select">
                    <option value="">全部状态</option>
                    <option value="active" @if(request('status') === 'active') selected @endif>活跃</option>
                    <option value="inactive" @if(request('status') === 'inactive') selected @endif>已停用</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">排序方式</label>
                <select name="sort" class="form-select">
                    <option value="created_at" @if(request('sort', 'created_at') === 'created_at') selected @endif>创建时间</option>
                    <option value="name" @if(request('sort') === 'name') selected @endif>姓名</option>
                    <option value="email" @if(request('sort') === 'email') selected @endif>邮箱</option>
                    <option value="last_login_at" @if(request('sort') === 'last_login_at') selected @endif>最后登录</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>搜索
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">管理员列表</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm" onclick="bulkAction('delete')" id="bulkDeleteBtn" style="display: none;">
                    <i class="bi bi-trash me-1"></i>删除选中
                </button>
                <button class="btn btn-outline-warning btn-sm" onclick="bulkAction('deactivate')" id="bulkDeactivateBtn" style="display: none;">
                    <i class="bi bi-person-dash me-1"></i>停用选中
                </button>
                <button class="btn btn-outline-success btn-sm" onclick="bulkAction('activate')" id="bulkActivateBtn" style="display: none;">
                    <i class="bi bi-person-check me-1"></i>激活选中
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        @if($users->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th>管理员信息</th>
                        <th>状态</th>
                        <th>角色</th>
                        <th>最后登录</th>
                        <th>创建时间</th>
                        <th width="150">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input user-checkbox" value="{{ $user->id }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($user->status === 'active')
                                <span class="badge bg-success">活跃</span>
                            @else
                                <span class="badge bg-secondary">已停用</span>
                            @endif
                        </td>
                        <td>
                            @if($user->is_super_admin)
                                <span class="badge bg-primary">
                                    <i class="bi bi-star-fill me-1"></i>超级管理员
                                </span>
                            @else
                                <span class="badge bg-light text-dark">普通管理员</span>
                            @endif
                        </td>
                        <td>
                            @if($user->last_login_at)
                                <span title="{{ $user->last_login_at }}">
                                    {{ $user->last_login_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-muted">从未登录</span>
                            @endif
                        </td>
                        <td>
                            <span title="{{ $user->created_at }}">
                                {{ $user->created_at->format('Y-m-d') }}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown">
                                    <i class="bi bi-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.admin-users.show', $user) }}">
                                            <i class="bi bi-eye me-2"></i>查看详情
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.admin-users.edit', $user) }}">
                                            <i class="bi bi-edit me-2"></i>编辑用户
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.admin-users.permissions', $user) }}">
                                            <i class="bi bi-shield me-2"></i>管理权限
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    @if($user->id !== auth('admin')->id())
                                    <li>
                                        <form action="{{ route('admin.admin-users.destroy', $user) }}" method="POST" 
                                              onsubmit="return confirm('确定要删除用户 {{ $user->name }} 吗？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>删除用户
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="bi bi-people" style="font-size: 48px; color: var(--text-muted);"></i>
            </div>
            <h5>暂无用户数据</h5>
            <p class="text-muted mb-4">
                @if(request()->hasAny(['search', 'status']))
                    没有找到符合条件的用户，请尝试调整筛选条件
                @else
                    还没有创建任何用户账户
                @endif
            </p>
            @if(!request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus me-2"></i>添加第一个用户
            </a>
            @endif
        </div>
        @endif
    </div>
    
    @if($users->hasPages())
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                显示 {{ $users->firstItem() }}-{{ $users->lastItem() }} 条，共 {{ $users->total() }} 条记录
            </div>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Bulk Action Form -->
<form id="bulkActionForm" method="POST" action="{{ route('admin.admin-users.bulk') }}" style="display: none;">
    @csrf
    <input type="hidden" name="action" id="bulkAction">
    <input type="hidden" name="ids" id="bulkIds">
</form>

@push('styles')
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        background: var(--primary-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 16px;
        flex-shrink: 0;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>
@endpush

@push('scripts')
<script>
// 全选功能
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkButtons();
});

// 单选功能
document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkButtons);
});

function updateBulkButtons() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkButtons = document.querySelectorAll('[id^="bulk"][id$="Btn"]');
    
    if (checkedBoxes.length > 0) {
        bulkButtons.forEach(btn => btn.style.display = 'inline-block');
    } else {
        bulkButtons.forEach(btn => btn.style.display = 'none');
    }
    
    // 更新全选状态
    const allCheckboxes = document.querySelectorAll('.user-checkbox');
    document.getElementById('selectAll').checked = checkedBoxes.length === allCheckboxes.length;
}

function bulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('请选择要操作的用户');
        return;
    }
    
    const ids = Array.from(checkedBoxes).map(cb => cb.value);
    
    let confirmMessage = '';
    switch (action) {
        case 'delete':
            confirmMessage = `确定要删除选中的 ${ids.length} 个用户吗？此操作不可撤销！`;
            break;
        case 'activate':
            confirmMessage = `确定要激活选中的 ${ids.length} 个用户吗？`;
            break;
        case 'deactivate':
            confirmMessage = `确定要停用选中的 ${ids.length} 个用户吗？`;
            break;
    }
    
    if (confirm(confirmMessage)) {
        document.getElementById('bulkAction').value = action;
        document.getElementById('bulkIds').value = ids.join(',');
        document.getElementById('bulkActionForm').submit();
    }
}
</script>
@endpush
@endsection