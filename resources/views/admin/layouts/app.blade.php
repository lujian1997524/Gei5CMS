<!DOCTYPE html>
<html lang="zh-CN" x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true',
    sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
    init() {
        this.$watch('sidebarOpen', (value) => {
            localStorage.setItem('sidebarOpen', value);
        });
    }
}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '仪表盘') - {{ config('app.name', 'Gei5CMS') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- macOS 15 Style CSS -->
    <style>
        :root {
            /* macOS 15 Color Palette */
            --primary-blue: #007AFF;
            --primary-green: #30D158;
            --light-blue: #E8F4FD;
            --light-green: #E8F8F0;
            --soft-gray: #F2F2F7;
            --border-color: #E5E5E7;
            --text-primary: #1C1C1E;
            --text-secondary: #8E8E93;
            --white: #FFFFFF;
            --shadow-light: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-dropdown: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            
            /* Layout */
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
            --header-height: 70px;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            
            /* Transitions */
            --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --transition-fast: all 0.2s ease-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--soft-gray);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Clean macOS Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            border-right: 1px solid var(--border-color);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .brand-logo {
            width: 32px;
            height: 32px;
            background: var(--primary-blue);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .brand-text {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            transition: var(--transition);
        }

        .sidebar.collapsed .brand-text {
            opacity: 0;
            width: 0;
        }

        /* Navigation */
        .nav-menu {
            padding: 20px 0;
        }

        .nav-group {
            margin-bottom: 30px;
        }

        .nav-group-title {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 20px;
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .sidebar.collapsed .nav-group-title {
            opacity: 0;
            height: 0;
            margin: 0;
        }

        .nav-item {
            margin: 2px 12px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            font-size: 14px;
            transition: var(--transition-fast);
            position: relative;
        }

        .nav-link:hover {
            background-color: var(--soft-gray);
            color: var(--text-primary);
            text-decoration: none;
        }

        .nav-link.active {
            background-color: var(--light-blue);
            color: var(--primary-blue);
            font-weight: 600;
        }

        .nav-icon {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            flex-shrink: 0;
            transition: var(--transition);
        }

        .nav-text {
            transition: var(--transition);
            white-space: nowrap;
        }

        /* Sub-menu items */
        .nav-sub-item {
            margin-left: 20px;
        }

        .nav-sub-item .nav-link {
            padding: 8px 12px;
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .nav-sub-item .nav-icon {
            width: 16px;
            height: 16px;
            margin-right: 10px;
        }

        .nav-sub-item .nav-link:hover {
            background-color: var(--soft-gray);
            color: var(--text-primary);
        }

        .nav-sub-item .nav-link.active {
            background-color: var(--light-blue);
            color: var(--primary-blue);
            font-weight: 500;
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
        }

        .sidebar.collapsed .nav-icon {
            margin-right: 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: var(--transition);
        }

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-collapsed);
        }

        /* Clean Header */
        .header {
            height: var(--header-height);
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--border-radius-sm);
            transition: var(--transition-fast);
        }

        .sidebar-toggle:hover {
            background-color: var(--soft-gray);
            color: var(--text-primary);
        }

        .breadcrumb {
            background: none;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }

        .breadcrumb-item {
            color: var(--text-secondary);
        }

        .breadcrumb-item.active {
            color: var(--text-primary);
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: var(--text-secondary);
            margin: 0 8px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 16px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--border-radius-sm);
            transition: var(--transition-fast);
        }

        .action-btn:hover {
            background-color: var(--soft-gray);
            color: var(--text-primary);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: var(--soft-gray);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition-fast);
            border: none;
        }

        .user-menu:hover {
            background-color: var(--light-blue);
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 13px;
            line-height: 1;
        }

        .user-role {
            color: var(--text-secondary);
            font-size: 11px;
            line-height: 1;
        }

        /* Content Area */
        .content-area {
            flex: 1;
            padding: 30px;
            background: var(--soft-gray);
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .page-description {
            color: var(--text-secondary);
            font-size: 16px;
            margin: 0;
        }

        /* macOS Cards */
        .card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-card);
            transition: var(--transition-fast);
        }

        .card:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-dropdown);
        }

        .card-header {
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 24px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .card-body {
            padding: 24px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 24px;
            transition: var(--transition-fast);
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-dropdown);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .stat-icon.blue {
            color: var(--primary-blue);
        }

        .stat-icon.green {
            color: var(--primary-green);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-change {
            font-size: 12px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--light-green);
            color: var(--primary-green);
        }

        /* Buttons */
        .btn {
            font-weight: 500;
            border-radius: var(--border-radius-sm);
            padding: 8px 16px;
            font-size: 14px;
            transition: var(--transition-fast);
            border: none;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #0056CC;
            color: white;
        }

        .btn-outline-primary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-outline-primary:hover {
            background: var(--light-blue);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-area {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--soft-gray);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="admin-layout">
        <!-- macOS Sidebar -->
        <aside class="sidebar" :class="{ 'collapsed': !sidebarOpen, 'open': sidebarOpen }">
            <!-- Brand -->
            <div class="sidebar-brand">
                <div class="brand-logo">
                    <i class="ti ti-rocket"></i>
                </div>
                <div class="brand-text">{{ config('app.name', 'Gei5CMS') }}</div>
            </div>

            <!-- Navigation -->
            <nav class="nav-menu">
                <!-- 首页 -->
                <div class="nav-group">
                    <div class="nav-group-title">概览</div>
                    <div class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon ti ti-home"></i>
                            <span class="nav-text">首页</span>
                        </a>
                    </div>
                </div>

                <!-- 应用管理 - 由主题和插件动态提供 -->
                @php
                    use App\Services\AdminMenuService;
                    
                    // 获取按位置分组的菜单
                    $topMenus = AdminMenuService::getMenusByPosition('top');
                    $middleMenus = AdminMenuService::getMenusByPosition('middle');
                    $bottomMenus = AdminMenuService::getMenusByPosition('bottom');
                    $activeTheme = \App\Models\Theme::where('status', 'active')->first();
                @endphp
                
                {{-- 顶部菜单组 --}}
                @if($topMenus)
                    @foreach($topMenus as $menu)
                        <div class="nav-group">
                            <div class="nav-group-title">{{ $menu['group'] ?? $menu['label'] }}</div>
                            <div class="nav-item">
                                <a href="{{ $menu['route'] }}" 
                                   class="nav-link {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                                    <i class="nav-icon ti {{ $menu['icon'] }}"></i>
                                    <span class="nav-text">{{ $menu['label'] }}</span>
                                </a>
                                @if(isset($menu['children']))
                                    @foreach($menu['children'] as $child)
                                        <div class="nav-item nav-sub-item">
                                            <a href="{{ $child['route'] }}" 
                                               class="nav-link {{ request()->routeIs($child['route']) ? 'active' : '' }}">
                                                <i class="nav-icon ti {{ $child['icon'] }}"></i>
                                                <span class="nav-text">{{ $child['label'] }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- 中间菜单组 - 主要应用功能 --}}
                @if($activeTheme && $middleMenus)
                    <div class="nav-group">
                        <div class="nav-group-title">{{ $activeTheme->name ?? '应用管理' }}</div>
                        @foreach($middleMenus as $menu)
                            <div class="nav-item">
                                <a href="{{ $menu['route'] }}" 
                                   class="nav-link {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                                    <i class="nav-icon ti {{ $menu['icon'] }}"></i>
                                    <span class="nav-text">{{ $menu['label'] }}</span>
                                </a>
                                @if(isset($menu['children']))
                                    @foreach($menu['children'] as $child)
                                        <div class="nav-item nav-sub-item">
                                            <a href="{{ $child['route'] }}" 
                                               class="nav-link {{ request()->routeIs($child['route']) ? 'active' : '' }}">
                                                <i class="nav-icon ti {{ $child['icon'] }}"></i>
                                                <span class="nav-text">{{ $child['label'] }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif($activeTheme && !$middleMenus)
                    {{-- 主题已激活但无菜单时显示示例菜单 --}}
                    <div class="nav-group">
                        <div class="nav-group-title">{{ $activeTheme->name ?? '应用管理' }}</div>
                        <div class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon ti ti-edit"></i>
                                <span class="nav-text">内容管理</span>
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon ti ti-chart-line"></i>
                                <span class="nav-text">数据统计</span>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- 底部菜单组 --}}
                @if($bottomMenus)
                    @foreach($bottomMenus as $menu)
                        <div class="nav-group">
                            <div class="nav-group-title">{{ $menu['group'] ?? $menu['label'] }}</div>
                            <div class="nav-item">
                                <a href="{{ $menu['route'] }}" 
                                   class="nav-link {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                                    <i class="nav-icon ti {{ $menu['icon'] }}"></i>
                                    <span class="nav-text">{{ $menu['label'] }}</span>
                                </a>
                                @if(isset($menu['children']))
                                    @foreach($menu['children'] as $child)
                                        <div class="nav-item nav-sub-item">
                                            <a href="{{ $child['route'] }}" 
                                               class="nav-link {{ request()->routeIs($child['route']) ? 'active' : '' }}">
                                                <i class="nav-icon ti {{ $child['icon'] }}"></i>
                                                <span class="nav-text">{{ $child['label'] }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                <!-- 扩展 - 默认框架菜单 -->
                <div class="nav-group">
                    <div class="nav-group-title">扩展</div>
                    <div class="nav-item">
                        <a href="{{ route('admin.themes.index') }}" class="nav-link">
                            <i class="nav-icon ti ti-palette"></i>
                            <span class="nav-text">主题</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="{{ route('admin.plugins.index') }}" class="nav-link">
                            <i class="nav-icon ti ti-puzzle"></i>
                            <span class="nav-text">插件</span>
                        </a>
                    </div>
                </div>

                <!-- 设置 - 默认框架菜单 -->
                <div class="nav-group">
                    <div class="nav-group-title">设置</div>
                    <div class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon ti ti-settings"></i>
                            <span class="nav-text">基础设置</span>
                        </a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Clean Header -->
            <header class="header">
                <div class="header-left">
                    <button @click="sidebarOpen = !sidebarOpen" class="sidebar-toggle">
                        <i class="ti ti-menu-2"></i>
                    </button>
                    
                    <nav>
                        <ol class="breadcrumb">
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                </div>

                <div class="header-right">
                    <button class="action-btn" title="搜索">
                        <i class="ti ti-search"></i>
                    </button>
                    
                    <button class="action-btn" title="通知">
                        <i class="ti ti-bell"></i>
                    </button>

                    <div class="dropdown">
                        <button class="user-menu" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                {{ substr(auth('admin')->user()->name ?? 'A', 0, 1) }}
                            </div>
                            <div class="user-info">
                                <div class="user-name">{{ auth('admin')->user()->name ?? '管理员' }}</div>
                                <div class="user-role">超级管理员</div>
                            </div>
                            <i class="ti ti-chevron-down" style="margin-left: 4px; font-size: 12px;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="#"><i class="ti ti-user me-2"></i>个人资料</a></li>
                            <li><a class="dropdown-item" href="#"><i class="ti ti-settings me-2"></i>账户设置</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="ti ti-logout me-2"></i>退出登录
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="content-area">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ti ti-check me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay d-md-none" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
    
    @stack('scripts')
</body>
</html>