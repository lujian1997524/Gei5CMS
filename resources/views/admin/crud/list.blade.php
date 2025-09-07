@extends('admin.layouts.app')

@section('title', $title . ' - Gei5CMS')

@push('styles')
<style>
    .list-actions {
        white-space: nowrap;
    }
    
    .bulk-actions {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
        display: none;
    }
    
    .bulk-actions.show {
        display: block;
    }
    
    .table-responsive {
        border-radius: 0.5rem;
    }
    
    .table thead th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        font-weight: 600;
        color: #374151;
        white-space: nowrap;
    }
    
    .sortable-header {
        cursor: pointer;
        user-select: none;
    }
    
    .sortable-header:hover {
        background: #f1f5f9;
    }
    
    .sort-icon {
        margin-left: 0.5rem;
        opacity: 0.5;
    }
    
    .sort-icon.active {
        opacity: 1;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .filter-bar {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $title }}</h1>
        <p class="text-muted mb-0">管理系统中的 {{ $title }}</p>
    </div>
    
    @if($can_create)
    <a href="{{ route('admin.' . $route . '.create') }}" class="btn btn-primary">
        <i class="bi bi-plus me-1"></i>
        创建{{ $title }}
    </a>
    @endif
</div>

<div class="card">
    <!-- 筛选栏 -->
    <div class="filter-bar">
        <form method="GET" class="d-flex gap-3 align-items-end">
            <!-- 搜索框 -->
            <div class="flex-grow-1">
                <label for="search" class="form-label small text-muted mb-1">搜索</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="search" name="search" class="form-control border-start-0" 
                           value="{{ $search_query }}" placeholder="输入关键词搜索...">
                </div>
            </div>
            
            @if(!empty($filters))
            @foreach($filters as $filter)
            <div>
                <label class="form-label small text-muted mb-1">{{ $filter['label'] }}</label>
                <select name="{{ $filter['name'] }}" class="form-select">
                    <option value="">全部</option>
                    @foreach($filter['options'] as $value => $label)
                    <option value="{{ $value }}" {{ request($filter['name']) == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endforeach
            @endif
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i>
                    搜索
                </button>
                <a href="{{ route('admin.' . $route . '.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>
                    清除
                </a>
            </div>
        </form>
    </div>

    <!-- 批量操作栏 -->
    <div class="bulk-actions" id="bulkActions">
        <form method="POST" action="{{ route('admin.' . $route . '.bulk') }}" id="bulkForm">
            @csrf
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">
                        已选择 <span id="selectedCount">0</span> 项
                    </span>
                    
                    <div class="d-flex gap-2">
                        <select name="action" class="form-select form-select-sm" style="width: auto;">
                            <option value="">选择操作</option>
                            @if($can_delete)
                            <option value="delete">删除选中项</option>
                            @endif
                            <option value="activate">激活选中项</option>
                            <option value="deactivate">停用选中项</option>
                        </select>
                        
                        <button type="submit" class="btn btn-sm btn-primary">
                            执行操作
                        </button>
                    </div>
                </div>
                
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                    取消选择
                </button>
            </div>
        </form>
    </div>

    <!-- 数据表格 -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th width="40">
                        <input type="checkbox" class="form-check-input" id="selectAll">
                    </th>
                    @foreach($columns as $column)
                    <th class="{{ $column['sortable'] ?? false ? 'sortable-header' : '' }}" 
                        @if($column['sortable'] ?? false) 
                        onclick="sortBy('{{ $column['name'] }}')"
                        @endif>
                        {{ $column['label'] }}
                        @if($column['sortable'] ?? false)
                        <i class="bi bi-chevron-down sort-icon {{ request('sort') == $column['name'] ? 'active' : '' }}"></i>
                        @endif
                    </th>
                    @endforeach
                    <th width="120" class="text-end">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input item-checkbox" 
                               name="ids[]" value="{{ $item->id }}">
                    </td>
                    @foreach($columns as $column)
                    <td>
                        @if($column['type'] === 'text')
                            {{ $item->{$column['name']} }}
                        @elseif($column['type'] === 'status')
                            @php
                                $status = $item->{$column['name']};
                                $statusClass = match($status) {
                                    'active' => 'bg-success',
                                    'inactive' => 'bg-secondary',
                                    'pending' => 'bg-warning',
                                    'error' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                $statusText = match($status) {
                                    'active' => '激活',
                                    'inactive' => '未激活',
                                    'pending' => '待处理',
                                    'error' => '错误',
                                    default => $status
                                };
                            @endphp
                            <span class="badge status-badge {{ $statusClass }}">{{ $statusText }}</span>
                        @elseif($column['type'] === 'date')
                            {{ $item->{$column['name']}?->format('Y-m-d H:i:s') ?? '-' }}
                        @elseif($column['type'] === 'image')
                            @if($item->{$column['name']})
                            <img src="{{ $item->{$column['name']} }}" alt="图片" 
                                 class="rounded" width="40" height="40" style="object-fit: cover;">
                            @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                            @endif
                        @elseif($column['type'] === 'boolean')
                            <i class="bi {{ $item->{$column['name']} ? 'bi-check text-success' : 'bi-x text-danger' }}"></i>
                        @else
                            {{ $item->{$column['name']} }}
                        @endif
                    </td>
                    @endforeach
                    <td class="list-actions text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.' . $route . '.show', $item->id) }}">
                                        <i class="bi bi-eye me-2"></i>查看
                                    </a>
                                </li>
                                @if($can_edit)
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.' . $route . '.edit', $item->id) }}">
                                        <i class="bi bi-edit me-2"></i>编辑
                                    </a>
                                </li>
                                @endif
                                @if($can_delete)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('admin.' . $route . '.destroy', $item->id) }}" 
                                          onsubmit="return confirm('确定要删除这个项目吗？')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-trash me-2"></i>删除
                                        </button>
                                    </form>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($columns) + 2 }}" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            @if($search_query)
                                没有找到匹配的结果
                            @else
                                暂无数据
                            @endif
                        </div>
                        @if($can_create && !$search_query)
                        <a href="{{ route('admin.' . $route . '.create') }}" class="btn btn-primary mt-3">
                            <i class="bi bi-plus me-1"></i>
                            创建第一个{{ $title }}
                        </a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 分页 -->
    @if($items->hasPages())
    <div class="card-footer border-top-0 bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                显示第 {{ $items->firstItem() }}-{{ $items->lastItem() }} 项，共 {{ $items->total() }} 项
            </div>
            {{ $items->links() }}
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // 全选功能
    const selectAll = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    selectAll.addEventListener('change', function() {
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.item-checkbox:checked');
        const count = selected.length;
        
        selectedCount.textContent = count;
        
        if (count > 0) {
            bulkActions.classList.add('show');
        } else {
            bulkActions.classList.remove('show');
        }
        
        selectAll.indeterminate = count > 0 && count < itemCheckboxes.length;
        selectAll.checked = count === itemCheckboxes.length;
    }

    function clearSelection() {
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        selectAll.checked = false;
        updateBulkActions();
    }

    // 排序功能
    function sortBy(column) {
        const url = new URL(window.location);
        const currentSort = url.searchParams.get('sort');
        const currentOrder = url.searchParams.get('order') || 'asc';
        
        if (currentSort === column) {
            url.searchParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
        } else {
            url.searchParams.set('sort', column);
            url.searchParams.set('order', 'asc');
        }
        
        window.location.href = url.toString();
    }

    // 批量操作确认
    document.getElementById('bulkForm').addEventListener('submit', function(e) {
        const action = this.querySelector('[name="action"]').value;
        const selected = document.querySelectorAll('.item-checkbox:checked');
        
        if (!action) {
            e.preventDefault();
            alert('请选择要执行的操作');
            return;
        }
        
        if (selected.length === 0) {
            e.preventDefault();
            alert('请选择要操作的项目');
            return;
        }
        
        // 将选中的ID添加到表单
        selected.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            this.appendChild(input);
        });
        
        if (action === 'delete') {
            if (!confirm(`确定要删除选中的 ${selected.length} 个项目吗？此操作无法撤销。`)) {
                e.preventDefault();
            }
        } else {
            if (!confirm(`确定要对选中的 ${selected.length} 个项目执行此操作吗？`)) {
                e.preventDefault();
            }
        }
    });
</script>
@endpush