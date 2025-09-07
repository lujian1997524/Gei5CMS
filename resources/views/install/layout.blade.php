<!DOCTYPE html>
<html lang="zh-CN" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- 防止缓存 -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $title ?? 'Gei5CMS 安装向导' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/icons/bootstrap-icons.min.css') }}">
    
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--soft-gray);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .install-container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            overflow: hidden;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }
        
        .install-header {
            background: var(--white);
            color: var(--text-primary);
            padding: 3rem 2rem 2rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .brand-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .brand-logo {
            width: 64px;
            height: 64px;
            background: var(--primary-blue);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: var(--shadow-card);
        }
        
        .brand-info h1 {
            font-size: 2.5rem;
            margin-bottom: 0.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }
        
        .brand-info p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
        }
        
        .install-content {
            padding: 4rem 3rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4rem;
            padding: 0 2rem;
            position: relative;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
            z-index: 2;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 3px solid var(--white);
            box-shadow: var(--shadow-light);
        }
        
        .step.active .step-number {
            background: var(--primary-blue);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0, 122, 255, 0.3);
        }
        
        .step.completed .step-number {
            background: var(--primary-green);
            color: white;
            box-shadow: 0 4px 15px rgba(48, 209, 88, 0.25);
        }
        
        .step-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-align: center;
            font-weight: 500;
            max-width: 120px;
        }
        
        .step.active .step-label {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .step.completed .step-label {
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .step-line {
            position: absolute;
            top: 30px;
            left: 50%;
            right: -50%;
            height: 3px;
            background: var(--border-color);
            z-index: 1;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .step:last-child .step-line {
            display: none;
        }
        
        .step.completed .step-line {
            background: var(--primary-green);
        }
        
        .step.active .step-line {
            background: linear-gradient(90deg, var(--primary-green) 0%, var(--border-color) 100%);
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        .form-input {
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
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.08);
        }
        
        .form-select {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            background: var(--white);
            color: var(--text-primary);
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.08);
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: inherit;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            color: white;
            box-shadow: var(--shadow-light);
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 122, 255, 0.25);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #6b7280;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: var(--primary-green);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .btn-block {
            width: 100%;
        }
        
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: #065f46;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #991b1b;
        }
        
        .alert-info {
            background: rgba(0, 122, 255, 0.1);
            border-color: rgba(0, 122, 255, 0.2);
            color: #1e40af;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-sm {
            font-size: 0.875rem;
        }
        
        .text-gray-600 {
            color: var(--text-secondary);
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        .requirements-list {
            list-style: none;
            margin: 1.5rem 0;
            background: var(--soft-gray);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
        }
        
        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .requirement-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .requirement-item:first-child {
            padding-top: 0;
        }
        
        .status-pass {
            color: var(--primary-green);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-fail {
            color: #ef4444;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .install-container {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* 响应式设计 */
        @media (max-width: 1024px) {
            .install-content {
                padding: 3rem 2rem;
            }
            
            .step-indicator {
                padding: 0 1rem;
                margin-bottom: 3rem;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .install-header {
                padding: 2rem 1rem;
            }
            
            .brand-section {
                flex-direction: column;
                gap: 1rem;
            }
            
            .brand-logo {
                width: 56px;
                height: 56px;
                font-size: 1.75rem;
            }
            
            .brand-info h1 {
                font-size: 2rem;
            }
            
            .install-content {
                padding: 2rem 1.5rem;
            }
            
            .step-indicator {
                flex-wrap: wrap;
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .step {
                flex: none;
                width: calc(50% - 0.5rem);
            }
            
            .step-line {
                display: none;
            }
            
            .footer-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .brand-info h1 {
                font-size: 1.75rem;
            }
            
            .step {
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .step-number {
                width: 50px;
                height: 50px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <div class="brand-section">
                <div class="brand-logo">
                    <i class="bi bi-hexagon-fill"></i>
                </div>
                <div class="brand-info">
                    <h1>Gei5CMS</h1>
                    <p>现代化内容管理系统</p>
                </div>
            </div>
        </div>
        @yield('content')
    </div>

    <script>
        function showAlert(message, type = 'error') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            
            const icon = document.createElement('i');
            if (type === 'success') {
                icon.className = 'bi bi-check-circle-fill';
            } else if (type === 'error') {
                icon.className = 'bi bi-exclamation-triangle-fill';
            } else {
                icon.className = 'bi bi-info-circle-fill';
            }
            
            const messageText = document.createElement('div');
            messageText.textContent = message;
            
            const closeButton = document.createElement('button');
            closeButton.innerHTML = '&times;';
            closeButton.className = 'btn-close';
            closeButton.onclick = () => alert.remove();
            
            alert.appendChild(icon);
            alert.appendChild(messageText);
            alert.appendChild(closeButton);
            
            const content = document.querySelector('.install-content');
            if (content) {
                content.insertBefore(alert, content.firstChild);
                
                // 自动关闭成功和信息提醒
                if (type === 'success' || type === 'info') {
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 5000);
                }
            }
        }

        // 表单增强
        document.addEventListener('DOMContentLoaded', function() {
            // 为所有按钮添加加载状态
            const buttons = document.querySelectorAll('.btn-primary');
            buttons.forEach(button => {
                if (button.type === 'submit' || button.closest('form')) {
                    button.addEventListener('click', function(e) {
                        if (this.disabled) return;
                        
                        const originalText = this.innerHTML;
                        this.disabled = true;
                        this.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> 处理中...';
                        
                        // 防止卡死，10秒后恢复
                        setTimeout(() => {
                            if (this.disabled) {
                                this.disabled = false;
                                this.innerHTML = originalText;
                            }
                        }, 10000);
                    });
                }
            });

            // 表单验证增强
            const inputs = document.querySelectorAll('.form-input, .form-select');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.classList.remove('is-invalid');
                });
            });

            // 步骤指示器动画
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                setTimeout(() => {
                    step.style.opacity = '0';
                    step.style.transform = 'translateY(20px)';
                    step.style.transition = 'all 0.3s ease';
                    
                    requestAnimationFrame(() => {
                        step.style.opacity = '1';
                        step.style.transform = 'translateY(0)';
                    });
                }, index * 100);
            });
        });

        // 添加CSS动画样式
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .form-input.is-invalid, .form-select.is-invalid {
                border-color: #ef4444;
                box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
            }
            
            .btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
                transform: none !important;
            }
            
            .me-2 {
                margin-right: 0.5rem;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>