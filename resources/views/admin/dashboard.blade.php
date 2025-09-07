@extends('admin.layouts.app')

@section('title', '仪表盘')

@section('breadcrumb')
<li class="breadcrumb-item active">仪表盘</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">仪表盘</h1>
            <p class="page-description">欢迎回来，{{ auth('admin')->user()->name ?? '管理员' }}</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise me-2" style="font-size: 14px;"></i>
                刷新数据
            </button>
            <a href="#" class="btn btn-primary">
                <i class="bi bi-plus me-2" style="font-size: 14px;"></i>
                快速创建
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    @foreach($stats as $key => $stat)
    <div class="stat-card">
        <div class="stat-header">
        <div class="stat-icon blue">
            @if($key === 'users')
                <i class="bi bi-people"></i>
            @elseif($key === 'admins')
                <i class="bi bi-shield-check"></i>
            @elseif($key === 'themes')
                <i class="bi bi-brush"></i>
            @elseif($key === 'plugins')
                <i class="bi bi-diagram-3"></i>
            @elseif($key === 'system')
                <i class="bi bi-server"></i>
            @elseif($key === 'storage')
                <i class="bi bi-hdd"></i>
            @elseif($key === 'performance')
                <i class="bi bi-graph-up"></i>
            @elseif($key === 'memory')
                <i class="bi bi-cpu"></i>
            @elseif($key === 'primary')
                <i class="bi bi-star-fill"></i>
            @elseif($key === 'secondary')
                <i class="bi bi-bar-chart"></i>
            @elseif($key === 'activity')
                <i class="bi bi-activity"></i>
            @elseif($key === 'overview')
                <i class="bi bi-eye"></i>
            @else
                <i class="bi bi-info-circle-fill"></i>
            @endif
        </div>
            @if(isset($stat['trend']) && isset($stat['trend_type']))
                <div class="stat-change trend-{{ $stat['trend_type'] }}">
                    <span>{{ $stat['trend'] }}</span>
                </div>
            @elseif($key === 'themes' && !\App\Models\Theme::where('status', 'active')->exists())
                <div class="stat-change trend-warning">
                    <span>需要选择</span>
                </div>
            @elseif($key === 'plugins')
                <div class="stat-change trend-info">
                    <span>可扩展</span>
                </div>
            @else
                <div class="stat-change trend-success">
                    <span>正常</span>
                </div>
            @endif
        </div>
        <div class="stat-value">{{ $stat['value'] }}</div>
        <div class="stat-label">{{ $stat['label'] }}</div>
        <div class="mt-2">
            <small class="text-muted">{{ $stat['description'] }}</small>
        </div>
        @if($key === 'performance' || $key === 'memory')
        <div class="stat-progress mt-2">
            <div class="progress-bar 
                @if($stat['trend_type'] === 'success') bg-success
                @elseif($stat['trend_type'] === 'warning') bg-warning  
                @elseif($stat['trend_type'] === 'danger') bg-danger
                @else bg-info
                @endif" 
                style="width: {{ $key === 'performance' ? min(100, max(10, 100 - (int)filter_var($stat['value'], FILTER_SANITIZE_NUMBER_INT) / 5)) : (strpos($stat['value'], '(') !== false ? (int)filter_var(substr($stat['value'], strpos($stat['value'], '(') + 1), FILTER_SANITIZE_NUMBER_INT) : 0) }}%">
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>

<div class="row g-4">
    <!-- System Status -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-activity me-2" style="color: var(--primary-blue);"></i>
                    系统状态
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @foreach($widgets as $key => $widget)
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <i class="{{ $widget['icon'] }}" 
                               style="font-size: 24px; margin-right: 15px; 
                                      color: {{ $widget['color'] === 'success' ? 'var(--primary-green)' : 'var(--primary-blue)' }};"></i>
                            <div>
                                <div class="fw-semibold text-primary fs-5 
                                    @if($key === 'system_status')
                                        @if($widget['value'] === '正常') text-success
                                        @elseif($widget['value'] === '警告') text-warning
                                        @else text-danger
                                        @endif
                                    @endif
                                ">{{ $widget['value'] }}</div>
                                <div class="fw-medium">{{ $widget['title'] }}</div>
                                <small class="text-muted">{{ $widget['description'] }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-zap me-2" style="color: var(--primary-green);"></i>
                    快捷操作
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}" class="btn btn-outline-primary text-start p-3" 
                       style="border-radius: var(--border-radius); text-decoration: none;">
                        <div class="d-flex align-items-center">
                            <i class="{{ $action['icon'] }}" 
                               style="font-size: 18px; margin-right: 12px; 
                                      color: {{ $action['color'] === 'primary' ? 'var(--primary-blue)' : 'var(--primary-green)' }};"></i>
                            <div>
                                <div class="fw-semibold">{{ $action['title'] }}</div>
                                <small class="text-muted">{{ $action['description'] }}</small>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
@if(!empty($recentActivity))
<div class="row g-4 mt-0">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-clock me-2" style="color: var(--text-secondary);"></i>
                    最近活动
                </h5>
            </div>
            <div class="card-body p-0">
                @foreach($recentActivity as $activity)
                <div class="d-flex align-items-start p-3 border-bottom" style="transition: var(--transition-fast);">
                    <i class="{{ $activity['icon'] }}" 
                       style="font-size: 20px; margin-right: 12px; 
                              color: {{ $activity['color'] === 'primary' ? 'var(--primary-blue)' : 'var(--primary-green)' }};"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $activity['title'] }}</div>
                        <div class="text-muted small">{{ $activity['description'] }}</div>
                        <small class="text-muted">{{ $activity['time'] }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    /* Additional dashboard styles */
    .btn-outline-primary:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-dropdown);
    }
    
    .stat-change {
        font-size: 11px;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 6px;
    }
    
    .trend-success {
        background: var(--light-green);
        color: var(--primary-green);
    }
    
    .trend-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .trend-danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .trend-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .trend-neutral {
        background: var(--soft-gray);
        color: var(--text-secondary);
    }
    
    .stat-progress {
        height: 4px;
        background-color: var(--soft-gray);
        border-radius: 2px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        transition: width 0.3s ease;
        border-radius: 2px;
    }
    
    .bg-success {
        background-color: var(--primary-green) !important;
    }
    
    .bg-warning {
        background-color: #ffc107 !important;
    }
    
    .bg-danger {
        background-color: #dc3545 !important;
    }
    
    .bg-info {
        background-color: var(--primary-blue) !important;
    }
    
    .card-body .border-bottom:last-child {
        border-bottom: none !important;
    }
    
    .card-body .border-bottom:hover {
        background-color: var(--soft-gray);
    }
    
    /* Enhanced stat cards */
    .stat-card {
        position: relative;
        transition: all 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-dropdown);
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
        margin: 8px 0 4px 0;
    }
    
    .stat-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0;
    }
</style>
@endpush
@endsection