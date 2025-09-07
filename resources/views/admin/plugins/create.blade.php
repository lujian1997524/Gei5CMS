@extends('admin.layouts.app')

@section('title', '上传插件')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">上传插件</h1>
            <p class="page-description">上传插件ZIP文件到系统</p>
        </div>
        <div>
            <a href="{{ route('admin.plugins.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2" style="font-size: 14px;"></i>
                返回列表
            </a>
        </div>
    </div>
</div>

<!-- Alerts -->
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-x me-2"></i>
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-x me-2"></i>
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Upload Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-upload me-2"></i>
                    插件文件上传
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.plugins.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- File Upload Area -->
                    <div class="upload-area mb-4">
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-icon">
                                <i class="bi bi-cloud-upload"></i>
                            </div>
                            <div class="upload-text">
                                <h4>选择插件文件或拖拽到此处</h4>
                                <p class="text-muted">支持 .zip 格式，文件大小限制 10MB</p>
                            </div>
                            <input type="file" 
                                   id="plugin_file" 
                                   name="plugin_file" 
                                   accept=".zip" 
                                   required 
                                   style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('plugin_file').click()">
                                <i class="bi bi-folder-open me-2"></i>
                                选择文件
                            </button>
                        </div>
                        
                        <!-- Selected file info -->
                        <div id="fileInfo" class="file-info mt-3" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-zip me-2 text-primary"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" id="fileName"></div>
                                    <div class="text-muted small" id="fileSize"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Instructions -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            插件上传说明
                        </h6>
                        <ul class="mb-0 small">
                            <li>插件必须是 ZIP 压缩包格式</li>
                            <li>压缩包根目录必须包含 <code>plugin.json</code> 配置文件</li>
                            <li>文件大小不能超过 10MB</li>
                            <li>确保插件来源可信，避免安全风险</li>
                            <li>上传后系统会自动解压并验证插件结构</li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.plugins.index') }}" class="btn btn-outline-secondary">
                            取消
                        </a>
                        <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                            <i class="bi bi-upload me-2"></i>
                            上传插件
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Upload Area Styles */
.upload-area {
    margin-bottom: 20px;
}

.upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius);
    padding: 40px 20px;
    text-align: center;
    background: var(--soft-gray);
    transition: var(--transition-fast);
    cursor: pointer;
}

.upload-zone:hover {
    border-color: var(--primary-blue);
    background: var(--light-blue);
}

.upload-zone.dragover {
    border-color: var(--primary-blue);
    background: var(--light-blue);
    transform: scale(1.02);
}

.upload-icon {
    font-size: 48px;
    color: var(--text-secondary);
    margin-bottom: 16px;
}

.upload-zone:hover .upload-icon,
.upload-zone.dragover .upload-icon {
    color: var(--primary-blue);
}

.upload-text h4 {
    color: var(--text-primary);
    margin-bottom: 8px;
    font-weight: 600;
}

.file-info {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    padding: 12px 16px;
}

.alert ul {
    padding-left: 20px;
}

.alert code {
    background: rgba(255,255,255,0.2);
    padding: 2px 4px;
    border-radius: 3px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('plugin_file');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadBtn = document.getElementById('uploadBtn');

    // Click to select file
    uploadZone.addEventListener('click', function() {
        fileInput.click();
    });

    // File input change
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });

    // Drag and drop
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });

    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            
            // Check file type
            if (!file.name.toLowerCase().endsWith('.zip')) {
                alert('请选择 ZIP 格式的插件文件');
                return;
            }

            // Check file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('文件大小不能超过 10MB');
                return;
            }

            // Update file input
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;

            // Show file info
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
            uploadBtn.disabled = false;
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    window.clearFile = function() {
        fileInput.value = '';
        fileInfo.style.display = 'none';
        uploadBtn.disabled = true;
    };
});
</script>
@endsection