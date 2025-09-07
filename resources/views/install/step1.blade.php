@extends('install.layout')

@section('content')
<div class="install-header">
    <h1>环境检测</h1>
    <p>检查服务器环境是否符合安装要求</p>
</div>

<div class="install-content">
    <!-- 步骤指示器 -->
    <div class="step-indicator">
        <div class="step active">
            <div class="step-number">1</div>
            <div class="step-label">环境检测</div>
            <div class="step-line"></div>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <div class="step-label">数据库配置</div>
            <div class="step-line"></div>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <div class="step-label">管理员设置</div>
            <div class="step-line"></div>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <div class="step-label">站点配置</div>
            <div class="step-line"></div>
        </div>
        <div class="step">
            <div class="step-number">5</div>
            <div class="step-label">安装完成</div>
        </div>
    </div>

    <!-- 系统要求检测 -->
    <div class="mb-4">
        <h3>系统要求</h3>
        <ul class="requirements-list">
            <li class="requirement-item">
                <span>{{ $requirements['requirements']['php_version']['name'] }}</span>
                <span class="{{ $requirements['requirements']['php_version']['status'] ? 'status-pass' : 'status-fail' }}">
                    {{ $requirements['requirements']['php_version']['status'] ? '✓ 通过' : '✗ 失败' }}
                    (当前: {{ $requirements['requirements']['php_version']['current'] }})
                </span>
            </li>
            @foreach($requirements['requirements']['extensions'] as $extension)
            <li class="requirement-item">
                <span>PHP {{ $extension['name'] }} 扩展</span>
                <span class="{{ $extension['status'] ? 'status-pass' : 'status-fail' }}">
                    {{ $extension['status'] ? '✓ 已安装' : '✗ 未安装' }}
                </span>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- 文件权限检测 -->
    <div class="mb-4">
        <h3>文件权限</h3>
        <ul class="requirements-list">
            @foreach($permissions['permissions'] as $permission)
            <li class="requirement-item">
                <span>{{ $permission['name'] }}</span>
                <span class="{{ $permission['status'] ? 'status-pass' : 'status-fail' }}">
                    {{ $permission['status'] ? '✓ 可写' : '✗ 不可写' }}
                </span>
            </li>
            @endforeach
        </ul>
    </div>

    @if(!$canContinue)
    <div class="alert alert-error">
        <strong>无法继续安装</strong><br>
        请解决上述问题后刷新页面重新检测。
    </div>
    @endif

    <div class="footer-buttons">
        <a href="{{ route('install.index') }}" class="btn btn-secondary">
            返回
        </a>
        
        @if($canContinue)
        <a href="{{ route('install.step', 2) }}" class="btn btn-primary">
            下一步
        </a>
        @else
        <button type="button" class="btn btn-primary" onclick="location.reload()">
            重新检测
        </button>
        @endif
    </div>
</div>
@endsection