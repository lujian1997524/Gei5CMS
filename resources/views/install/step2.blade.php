@extends('install.layout')

@section('content')
<div class="install-content">
    <!-- 步骤指示器 -->
    <div class="step-indicator">
        <div class="step completed">
            <div class="step-number">1</div>
            <div class="step-label">环境检测</div>
            <div class="step-line"></div>
        </div>
        <div class="step active">
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

    <div style="text-align: center; margin-bottom: 3rem;">
        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary);">数据库配置</h2>
        <p style="color: var(--text-secondary);">请填写数据库连接信息</p>
    </div>

    <form id="database-form" style="max-width: 600px; margin: 0 auto;">
        @csrf
        <div class="form-group">
            <label for="db_host" class="form-label">数据库主机</label>
            <input type="text" id="db_host" name="db_host" class="form-input" value="127.0.0.1" required>
        </div>

        <div class="form-group">
            <label for="db_port" class="form-label">端口</label>
            <input type="number" id="db_port" name="db_port" class="form-input" value="3306" required>
        </div>

        <div class="form-group">
            <label for="db_name" class="form-label">数据库名</label>
            <input type="text" id="db_name" name="db_name" class="form-input" placeholder="gei5cms" required>
        </div>

        <div class="form-group">
            <label for="db_username" class="form-label">用户名</label>
            <input type="text" id="db_username" name="db_username" class="form-input" placeholder="root" required>
        </div>

        <div class="form-group">
            <label for="db_password" class="form-label">密码</label>
            <input type="password" id="db_password" name="db_password" class="form-input" placeholder="留空表示无密码">
        </div>

        <div class="footer-buttons">
            <a href="{{ route('install.step', 1) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                上一步
            </a>
            <button type="submit" class="btn btn-primary" id="next-btn">
                <i class="bi bi-database me-2"></i>
                测试连接并继续
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('1. DOMContentLoaded 事件触发');
    
    // 检查浏览器兼容性
    if (!window.fetch) {
        console.error('浏览器不支持 fetch API');
        showAlert('浏览器版本过低，请使用现代浏览器', 'error');
        return;
    }
    
    if (!window.FormData) {
        console.error('浏览器不支持 FormData');
        showAlert('浏览器版本过低，请使用现代浏览器', 'error');
        return;
    }
    
    const form = document.getElementById('database-form');
    const btn = document.getElementById('next-btn');
    
    console.log('2. 表单元素:', form);
    console.log('3. 按钮元素:', btn);
    
    if (!form || !btn) {
        console.error('找不到表单或按钮元素！');
        showAlert('页面加载异常，请刷新重试', 'error');
        return;
    }
    
    // 提取表单提交逻辑为独立函数
    function handleFormSubmission() {
        console.log('4. 手动表单提交开始');
        
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>测试中...';
        btn.disabled = true;
        console.log('5. 按钮状态已更新');
        
        try {
            // 检查必填字段
            const host = document.getElementById('db_host').value;
            const port = document.getElementById('db_port').value;
            const name = document.getElementById('db_name').value;
            const username = document.getElementById('db_username').value;
            
            console.log('6. 字段值检查:', { host, port, name, username });
            
            if (!host || !port || !name || !username) {
                throw new Error('请填写所有必填字段');
            }
            
            // 使用FormData包含CSRF token
            const formData = new FormData(form);
            console.log('7. FormData 创建成功');
            console.log('8. 表单数据:', {
                host: formData.get('db_host'),
                port: formData.get('db_port'),
                name: formData.get('db_name'),
                username: formData.get('db_username'),
                token: formData.get('_token') ? '已包含' : '缺失'
            });
            
            // 验证FormData内容
            for (let pair of formData.entries()) {
                console.log('FormData 项:', pair[0], '=', pair[1]);
            }
            
            const url = '{{ route("install.database") }}';
            console.log('9. 开始发送 fetch 请求到:', url);
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('10. 收到响应:', response.status, response.statusText);
                console.log('10a. 响应头:', Object.fromEntries(response.headers.entries()));
                
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
                console.log('11. 响应数据:', data);
                if (data && data.success) {
                    setTimeout(() => {
                        window.location.href = '{{ route("install.step", 3) }}';
                    }, 500);
                } else {
                    const errorMsg = (data && data.error) || '数据库连接失败';
                    showAlert(errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error('12. 请求异常:', error);
                showAlert('请求失败：' + error.message, 'error');
            })
            .finally(() => {
                console.log('13. 请求完成，恢复按钮状态');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        } catch (error) {
            console.error('14. JavaScript 异常:', error);
            showAlert('JavaScript 错误：' + error.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    // 添加按钮点击监听器用于诊断
    btn.addEventListener('click', function(e) {
        console.log('3a. 按钮点击事件触发');
        console.log('3b. 事件对象:', e);
        console.log('3c. 按钮类型:', btn.type);
        console.log('3d. 表单有效性:', form.checkValidity ? form.checkValidity() : 'checkValidity不支持');
        
        // 阻止默认的表单提交，手动处理
        e.preventDefault();
        
        // 手动触发我们的提交逻辑
        handleFormSubmission();
    });
    
    form.addEventListener('submit', function(e) {
        console.log('4. submit 事件触发（备用）');
        e.preventDefault();
        handleFormSubmission();
    });
    
    console.log('15. 事件监听器绑定完成');
});
</script>
@endsection