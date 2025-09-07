@extends('install.layout')

@section('content')
<div class="install-header">
    <h1>欢迎使用 Gei5CMS</h1>
    <p>现代化多形态Web应用框架安装向导</p>
</div>

<div class="install-content">
    <div class="text-center mb-4">
        <h2>感谢您选择 Gei5CMS</h2>
        <p class="text-gray-600">
            Gei5CMS是一个现代化的多形态Web应用框架，<br>
            支持插件系统、主题系统和强大的钩子机制。
        </p>
    </div>

    <div class="mb-4">
        <h3>安装前准备</h3>
        <ul style="list-style: disc; margin-left: 20px; margin-top: 10px;">
            <li>确保您的服务器满足系统要求</li>
            <li>准备MySQL数据库信息</li>
            <li>确保相关目录具有写入权限</li>
            <li>备份现有数据（如果有的话）</li>
        </ul>
    </div>

    <div class="text-center" style="margin-top: 40px;">
        <a href="{{ route('install.step', 1) }}" class="btn btn-primary btn-block">
            开始安装
        </a>
    </div>

    <div class="text-center" style="margin-top: 20px;">
        <p class="text-sm text-gray-600">
            安装过程大约需要2-3分钟
        </p>
    </div>
</div>
@endsection