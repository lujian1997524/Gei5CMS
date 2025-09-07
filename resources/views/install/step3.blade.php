@extends('install.layout')

@section('content')
<div class="install-header">
    <h1>管理员设置</h1>
    <p>创建系统管理员账户</p>
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
        <div class="step active">
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

    <form id="admin-form">
        @csrf
        <div class="form-group">
            <label for="admin_name" class="form-label">用户名</label>
            <input type="text" id="admin_name" name="admin_name" class="form-input" placeholder="admin" required>
            <small class="text-sm text-gray-600">用于登录后台管理系统</small>
        </div>

        <div class="form-group">
            <label for="admin_email" class="form-label">邮箱地址</label>
            <input type="email" id="admin_email" name="admin_email" class="form-input" placeholder="admin@example.com" required>
            <small class="text-sm text-gray-600">用于找回密码和接收系统通知</small>
        </div>

        <div class="form-group">
            <label for="admin_password" class="form-label">密码</label>
            <input type="password" id="admin_password" name="admin_password" class="form-input" placeholder="请输入密码" required minlength="8">
            <small class="text-sm text-gray-600">至少8位字符，建议包含字母、数字和特殊字符</small>
        </div>

        <div class="form-group">
            <label for="admin_password_confirmation" class="form-label">确认密码</label>
            <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" class="form-input" placeholder="请再次输入密码" required>
        </div>

        <div class="footer-buttons">
            <a href="{{ route('install.step', 2) }}" class="btn btn-secondary">
                上一步
            </a>
            <button type="submit" class="btn btn-primary" id="next-btn">
                创建管理员账户
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('admin-form');
    const btn = document.getElementById('next-btn');
    
    if (!form || !btn) {
        console.error('找不到表单或按钮元素！');
        showAlert('页面加载异常，请刷新重试', 'error');
        return;
    }
    
    // 提取表单提交逻辑为独立函数
    function handleFormSubmission() {
        // 验证密码匹配
        const password = document.getElementById('admin_password').value;
        const confirmPassword = document.getElementById('admin_password_confirmation').value;
        
        if (password !== confirmPassword) {
            showAlert('两次输入的密码不一致', 'error');
            return;
        }
        
        // 检查必填字段
        const name = document.getElementById('admin_name').value;
        const email = document.getElementById('admin_email').value;
        
        if (!name || !email || !password || !confirmPassword) {
            showAlert('请填写所有必填字段', 'error');
            return;
        }
        
        const originalText = btn.textContent;
        btn.textContent = '创建中...';
        btn.disabled = true;
        
        try {
            const formData = new FormData(form);
            
            fetch('{{ route("install.admin") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('响应内容:', text);
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error('期待JSON但收到:', text);
                        throw new Error('服务器响应格式错误');
                    });
                }
            })
            .then(data => {
                if (data && data.success) {
                    setTimeout(() => {
                        window.location.href = '{{ route("install.step", 4) }}';
                    }, 500);
                } else {
                    const errorMsg = (data && data.error) || '创建管理员账户失败';
                    showAlert(errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error('请求异常:', error);
                showAlert('请求失败：' + error.message, 'error');
            })
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            });
        } catch (error) {
            console.error('JavaScript 异常:', error);
            showAlert('JavaScript 错误：' + error.message, 'error');
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }
    
    // 添加按钮点击监听器 - 主要处理方式
    btn.addEventListener('click', function(e) {
        // 阻止默认的表单提交，手动处理
        e.preventDefault();
        
        // 手动触发我们的提交逻辑
        handleFormSubmission();
    });
    
    // 添加表单提交监听器作为备用
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        handleFormSubmission();
    });
});

// 密码强度检测
document.getElementById('admin_password').addEventListener('input', function() {
    const password = this.value;
    const strength = checkPasswordStrength(password);
    // 可以添加密码强度显示逻辑
});

function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    return strength;
}
</script>
@endsection