@extends('install.layout')

@section('content')
<div class="install-header">
    <h1>安装完成</h1>
    <p>恭喜！Gei5CMS 已成功安装</p>
</div>

<div class="install-content">
    <!-- 步骤指示器 -->
    <div class="step-indicator">
        <div class="step completed">
            <div class="step-number">1</div>
            <div class="step-label">环境检测</div>
            <div class="step-line"></div>
        </div>
        <div class="step completed">
            <div class="step-number">2</div>
            <div class="step-label">数据库配置</div>
            <div class="step-line"></div>
        </div>
        <div class="step completed">
            <div class="step-number">3</div>
            <div class="step-label">管理员设置</div>
            <div class="step-line"></div>
        </div>
        <div class="step completed">
            <div class="step-number">4</div>
            <div class="step-label">站点配置</div>
            <div class="step-line"></div>
        </div>
        <div class="step completed">
            <div class="step-number">5</div>
            <div class="step-label">安装完成</div>
        </div>
    </div>

    <div class="text-center mb-4">
        <div class="alert alert-success">
            <strong>🎉 安装成功！</strong><br>
            您的 Gei5CMS 系统已成功安装并配置完成。
        </div>
    </div>

    <!-- 安装摘要信息 -->
    <div class="mb-4">
        <h3>安装摘要</h3>
        <div class="requirements-list" style="margin-top: 20px;">
            <div class="requirement-item">
                <span>网站名称</span>
                <span class="status-pass">{{ $installData['site_name'] ?? '未设置' }}</span>
            </div>
            <div class="requirement-item">
                <span>管理员账户</span>
                <span class="status-pass">{{ $installData['admin_username'] ?? 'admin' }}</span>
            </div>
            <div class="requirement-item">
                <span>管理员邮箱</span>
                <span class="status-pass">{{ $installData['admin_email'] ?? '未设置' }}</span>
            </div>
            <div class="requirement-item">
                <span>网站地址</span>
                <span class="status-pass">{{ $installData['site_url'] ?? request()->getSchemeAndHttpHost() }}</span>
            </div>
            <div class="requirement-item">
                <span>数据库连接</span>
                <span class="status-pass">✓ 已连接</span>
            </div>
            <div class="requirement-item">
                <span>数据表创建</span>
                <span class="status-pass">✓ 已完成</span>
            </div>
        </div>
    </div>

    <!-- 重要提醒 -->
    <div class="mb-4">
        <h3>重要提醒</h3>
        <div class="alert alert-error">
            <strong>⚠️ 安全提醒</strong><br>
            为了系统安全，请在安装完成后：
            <ul style="margin: 10px 0 0 20px;">
                <li>删除或重命名 install 安装目录</li>
                <li>修改默认的数据库配置</li>
                <li>定期备份数据库和重要文件</li>
                <li>关闭调试模式（如果已开启）</li>
            </ul>
        </div>
    </div>

    <!-- 下一步操作 -->
    <div class="mb-4">
        <h3>下一步操作</h3>
        <ul style="list-style: disc; margin-left: 20px; margin-top: 10px;">
            <li>登录后台管理系统，完善网站设置</li>
            <li>安装和激活需要的主题</li>
            <li>安装和配置需要的插件</li>
            <li>创建网站内容和页面</li>
            <li>配置SEO和网站优化设置</li>
        </ul>
    </div>

    <!-- 快捷链接 -->
    <div class="text-center" style="margin-top: 40px;">
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                进入后台管理
            </a>
            <a href="{{ url('/') }}" class="btn btn-success">
                访问网站首页
            </a>
        </div>
    </div>

    <!-- 技术支持信息 -->
    <div class="text-center" style="margin-top: 30px;">
        <p class="text-sm text-gray-600">
            感谢使用 Gei5CMS！<br>
            如需技术支持，请访问 <a href="#" target="_blank" style="color: #2563eb;">官方文档</a> 或 <a href="#" target="_blank" style="color: #2563eb;">社区论坛</a>
        </p>
        <p class="text-sm text-gray-600" style="margin-top: 10px;">
            版本: {{ config('app.version', '1.0.0') }} | 
            安装时间: {{ now()->format('Y-m-d H:i:s') }}
        </p>
    </div>
</div>

<script>
// 安装完成后清理安装缓存
document.addEventListener('DOMContentLoaded', function() {
    // 可以添加安装完成后的清理逻辑
    console.log('Gei5CMS 安装完成');
    
    // 可选：自动跳转倒计时
    // setTimeout(function() {
    //     window.location.href = '{{ route("admin.dashboard") }}';
    // }, 10000); // 10秒后自动跳转
});
</script>
@endsection