@extends('install.layout')

@section('content')
<div class="install-header">
    <h1>站点配置</h1>
    <p>配置您的网站基本信息</p>
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
        <div class="step active">
            <div class="step-number">4</div>
            <div class="step-label">站点配置</div>
            <div class="step-line"></div>
        </div>
        <div class="step">
            <div class="step-number">5</div>
            <div class="step-label">安装完成</div>
        </div>
    </div>

    <form id="site-form">
        @csrf
        <div class="form-group">
            <label for="app_name" class="form-label">网站名称</label>
            <input type="text" id="app_name" name="app_name" class="form-input" placeholder="我的网站" required>
            <small class="text-sm text-gray-600">显示在网站标题和浏览器标签页</small>
        </div>

        <div class="form-group">
            <label for="site_description" class="form-label">网站描述</label>
            <textarea id="site_description" name="site_description" class="form-input" rows="3" placeholder="网站简介和描述信息..."></textarea>
            <small class="text-sm text-gray-600">用于SEO和网站简介</small>
        </div>

        <div class="form-group">
            <label for="site_keywords" class="form-label">网站关键词</label>
            <input type="text" id="site_keywords" name="site_keywords" class="form-input" placeholder="关键词1,关键词2,关键词3">
            <small class="text-sm text-gray-600">多个关键词用英文逗号分隔</small>
        </div>

        <div class="form-group">
            <label for="app_url" class="form-label">网站域名</label>
            <input type="url" id="app_url" name="app_url" class="form-input" placeholder="https://www.example.com" required>
            <small class="text-sm text-gray-600">网站的完整访问地址</small>
        </div>

        <div class="form-group">
            <label for="timezone" class="form-label">时区设置</label>
            <select id="timezone" name="timezone" class="form-select" required>
                <option value="Asia/Shanghai" selected>北京时间 (UTC+8)</option>
                <option value="Asia/Hong_Kong">香港时间 (UTC+8)</option>
                <option value="Asia/Taipei">台北时间 (UTC+8)</option>
                <option value="UTC">世界标准时间 (UTC)</option>
                <option value="America/New_York">纽约时间 (UTC-5/-4)</option>
                <option value="Europe/London">伦敦时间 (UTC+0/+1)</option>
                <option value="Asia/Tokyo">东京时间 (UTC+9)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="language" class="form-label">默认语言</label>
            <select id="language" name="language" class="form-select" required>
                <option value="zh_CN" selected>简体中文</option>
                <option value="zh_TW">繁体中文</option>
                <option value="en_US">English</option>
                <option value="ja_JP">日本語</option>
                <option value="ko_KR">한국어</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">功能设置</label>
            <div style="margin-top: 10px;">
                <label style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="checkbox" name="enable_user_registration" value="1" checked style="margin-right: 8px;">
                    允许用户注册
                </label>
                <label style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="checkbox" name="enable_comments" value="1" checked style="margin-right: 8px;">
                    启用评论功能
                </label>
                <label style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="checkbox" name="enable_cache" value="1" checked style="margin-right: 8px;">
                    启用页面缓存
                </label>
                <label style="display: flex; align-items: center;">
                    <input type="checkbox" name="enable_debug" value="1" style="margin-right: 8px;">
                    开启调试模式（生产环境请关闭）
                </label>
            </div>
        </div>

        <div class="footer-buttons">
            <a href="{{ route('install.step', 3) }}" class="btn btn-secondary">
                上一步
            </a>
            <button type="submit" class="btn btn-primary" id="next-btn">
                保存配置并继续
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 自动检测当前域名
    const appUrlInput = document.getElementById('app_url');
    if (!appUrlInput.value) {
        appUrlInput.value = window.location.origin;
    }
    
    const form = document.getElementById('site-form');
    const btn = document.getElementById('next-btn');
    
    if (!form || !btn) {
        console.error('找不到表单或按钮元素！');
        showAlert('页面加载异常，请刷新重试', 'error');
        return;
    }
    
    // 提取表单提交逻辑为独立函数
    function handleFormSubmission() {
        // 检查必填字段
        const appName = document.getElementById('app_name').value;
        const appUrl = document.getElementById('app_url').value;
        const timezone = document.getElementById('timezone').value;
        const language = document.getElementById('language').value;
        
        if (!appName || !appUrl || !timezone || !language) {
            showAlert('请填写所有必填字段', 'error');
            return;
        }
        
        const originalText = btn.textContent;
        btn.textContent = '保存中...';
        btn.disabled = true;
        
        try {
            const formData = new FormData(form);
            
            fetch('{{ route("install.site") }}', {
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
                        window.location.href = '{{ route("install.step", 5) }}';
                    }, 500);
                } else {
                    const errorMsg = (data && data.error) || '保存配置失败';
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

// 网站名称实时预览
document.addEventListener('DOMContentLoaded', function() {
    const appNameInput = document.getElementById('app_name');
    if (appNameInput) {
        appNameInput.addEventListener('input', function() {
            // 可以添加实时预览功能
        });
    }
});
</script>
@endsection