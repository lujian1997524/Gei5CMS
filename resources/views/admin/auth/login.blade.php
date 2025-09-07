<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>管理员登录 - Gei5CMS</title>
    
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="{{ asset('assets/icons/tabler-icons.min.css') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
            --border-radius: 20px;
            --border-radius-sm: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
        }

        .login-wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        .login-image-section {
            flex: 1.2;
            position: relative;
            background: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(0, 122, 255, 0.15), rgba(48, 209, 88, 0.1));
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 3rem;
            color: white;
        }

        .image-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .image-description {
            font-size: 1.1rem;
            opacity: 0.95;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .login-form-section {
            flex: 1;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            min-width: 480px;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .brand-logo {
            width: 72px;
            height: 72px;
            background: var(--primary-blue);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            box-shadow: var(--shadow-card);
        }

        .brand-name {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-family: inherit;
            background: var(--white);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.08);
        }

        .form-control.is-invalid {
            border-color: #ef4444;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #ef4444;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            background: var(--white);
        }

        .form-check-input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .form-check-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-primary {
            width: 100%;
            background: var(--primary-blue);
            border: none;
            color: white;
            padding: 1.125rem 2rem;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0, 122, 255, 0.24);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #065f46;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #991b1b;
        }

        .alert-info {
            background: rgba(0, 122, 255, 0.1);
            border: 1px solid rgba(0, 122, 255, 0.3);
            color: #1e40af;
        }

        .btn-close {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            line-height: 1;
            color: inherit;
            opacity: 0.5;
            cursor: pointer;
            margin-left: auto;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .create-admin-hint {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .create-admin-hint small {
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: block;
            margin-bottom: 1rem;
        }

        .btn-outline-secondary {
            background: transparent;
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-outline-secondary:hover {
            background: var(--soft-gray);
            border-color: var(--text-secondary);
            color: var(--text-primary);
            text-decoration: none;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @media (max-width: 1024px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-image-section {
                flex: none;
                height: 30vh;
            }

            .login-form-section {
                flex: 1;
                min-width: auto;
                padding: 2rem;
                overflow-y: auto;
            }

            .brand-header {
                margin-bottom: 2rem;
            }

            .login-header {
                margin-bottom: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .login-form-section {
                padding: 1.5rem;
            }

            .brand-name {
                font-size: 2rem;
            }

            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side: Bing Daily Image -->
        <div class="login-image-section">
            <img id="bingImage" class="login-image" src="" alt="Bing Daily Image" loading="lazy">
            <div class="image-overlay">
                <h2 id="imageTitle" class="image-title">加载中...</h2>
                <p id="imageDescription" class="image-description">正在获取今日精美壁纸</p>
            </div>
        </div>
        
        <!-- Right Side: Login Form -->
        <div class="login-form-section">
            <!-- Brand Header -->
            <div class="brand-header">
                <div class="brand-logo">
                    <i class="bi bi-hexagon-3d"></i>
                </div>
                <h1 class="brand-name">Gei5CMS</h1>
                <p class="brand-subtitle">现代化内容管理系统</p>
            </div>
            
            <!-- Login Header -->
            <div class="login-header">
                <h2 class="login-title">欢迎回来</h2>
                <p class="login-subtitle">请输入您的登录凭据以访问管理后台</p>
            </div>
            
            <!-- Messages -->
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif
            
            <!-- Login Form -->
            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                
                <div class="form-group">
                    <label for="login" class="form-label">
                        <i class="bi bi-user" style="margin-right: 0.5rem;"></i>用户名或邮箱
                    </label>
                    <input 
                        type="text" 
                        class="form-control @error('login') is-invalid @enderror" 
                        id="login" 
                        name="login" 
                        placeholder="请输入用户名或邮箱地址"
                        value="{{ old('login') }}"
                        required
                        autofocus
                    >
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="bi bi-key" style="margin-right: 0.5rem;"></i>密码
                    </label>
                    <input 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        placeholder="请输入您的登录密码"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">
                        保持登录状态
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-login"></i>
                    登录到管理后台
                </button>
            </form>
            
            @if(app()->environment('local'))
            <div class="create-admin-hint">
                <small>开发环境：还没有管理员账户？</small>
                <a href="{{ route('admin.create-default') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-user-plus"></i>
                    创建默认管理员账户
                </a>
            </div>
            @endif
        </div>
    </div>
    
    <script>
        // Fetch Bing Daily Image using a CORS proxy or server-side endpoint
        async function loadBingImage() {
            try {
                // 使用免费的CORS代理服务
                const proxyUrl = 'https://api.allorigins.win/get?url=';
                const bingUrl = encodeURIComponent('https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=zh-CN');
                const response = await fetch(proxyUrl + bingUrl);
                const proxyData = await response.json();
                const data = JSON.parse(proxyData.contents);
                
                if (data && data.images && data.images.length > 0) {
                    const imageData = data.images[0];
                    const imageUrl = 'https://www.bing.com' + imageData.url.replace('_1366x768', '_1920x1080');
                    
                    const imageElement = document.getElementById('bingImage');
                    const titleElement = document.getElementById('imageTitle');
                    const descriptionElement = document.getElementById('imageDescription');
                    
                    imageElement.src = imageUrl;
                    titleElement.textContent = imageData.title || '今日壁纸';
                    descriptionElement.textContent = imageData.copyright || '来自 Microsoft Bing';
                } else {
                    // 如果没有数据，使用默认内容
                    throw new Error('No image data found');
                }
            } catch (error) {
                console.warn('Failed to load Bing image:', error);
                // 使用预设的精美图片作为后备方案
                const imageElement = document.getElementById('bingImage');
                const titleElement = document.getElementById('imageTitle');
                const descriptionElement = document.getElementById('imageDescription');
                
                // 使用Unsplash的每日精选图片作为后备
                const fallbackImages = [
                    {
                        url: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
                        title: '壮美山景',
                        description: '探索自然之美'
                    },
                    {
                        url: 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
                        title: '森林晨光',
                        description: '宁静致远'
                    },
                    {
                        url: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
                        title: '湖光山色',
                        description: '心旷神怡'
                    }
                ];
                
                const randomImage = fallbackImages[Math.floor(Math.random() * fallbackImages.length)];
                imageElement.src = randomImage.url;
                titleElement.textContent = randomImage.title;
                descriptionElement.textContent = randomImage.description;
            }
        }

        // 登录表单提交处理
        const loginForm = document.querySelector('form');
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i>登录中...';
            
            // 防止卡死
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }, 10000);
        });

        // 自动关闭成功消息
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            });
        }, 3000);

        // 页面加载完成后获取图片
        document.addEventListener('DOMContentLoaded', loadBingImage);
    </script>
</body>
</html>