<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>管理员登录 - Gei5CMS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@2.44.0/tabler-icons.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background: #1e293b;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.8;
        }

        .login-body {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgb(59 130 246 / 0.25);
        }

        .btn-primary {
            background: #3b82f6;
            border-color: #3b82f6;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .create-admin-hint {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .create-admin-hint small {
            color: #6b7280;
        }

        .create-admin-hint .btn {
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <i class="ti ti-hexagon-3d fs-1 me-2"></i>
                <h1>Gei5CMS</h1>
            </div>
            <p>管理员登录</p>
        </div>
        
        <div class="login-body">
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

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="ti ti-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                
                <div class="form-floating">
                    <input 
                        type="text" 
                        class="form-control @error('login') is-invalid @enderror" 
                        id="login" 
                        name="login" 
                        placeholder="用户名或邮箱"
                        value="{{ old('login') }}"
                        required
                        autofocus
                    >
                    <label for="login">
                        <i class="ti ti-user me-2"></i>用户名或邮箱
                    </label>
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating">
                    <input 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        placeholder="Password"
                        required
                    >
                    <label for="password">
                        <i class="ti ti-key me-2"></i>密码
                    </label>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">
                        记住我
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-login me-2"></i>
                    登录
                </button>
            </form>

            @if(app()->environment('local'))
            <div class="create-admin-hint">
                <small>开发环境：还没有管理员账户？</small><br>
                <a href="{{ route('admin.create-default') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-user-plus me-1"></i>
                    创建默认管理员
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 自动关闭成功消息
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                if (alert.querySelector('.btn-close')) {
                    alert.querySelector('.btn-close').click();
                }
            });
        }, 3000);

        // 登录表单提交处理
        const loginForm = document.querySelector('form');
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ti ti-loader animate-spin me-2"></i>登录中...';
            
            // 防止卡死
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }, 10000);
        });
    </script>
</body>
</html>