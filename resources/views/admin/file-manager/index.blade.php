@extends('admin.layouts.app')

@section('title', '文件管理')

@section('content')
<div class="page-wrapper">
    <div class="page-body">
        <div class="container-fluid">
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="card-title">文件管理</h3>
                                    @if($currentFolder)
                                        <div class="card-subtitle mt-1">
                                            当前位置: {{ $currentFolder->path }}
                                        </div>
                                    @endif
                                </div>
                                <div class="btn-list">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                                        <i class="bi bi-folder-plus"></i>
                                        新建文件夹
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="document.getElementById('fileUpload').click()">
                                        <i class="bi bi-upload"></i>
                                        上传文件
                                    </button>
                                    <input type="file" id="fileUpload" multiple style="display: none;" accept="*/*">
                                </div>
                            </div>
                        </div>

                        <div class="card-header border-0 pb-0">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb breadcrumb-arrows">
                                    @foreach($breadcrumb as $crumb)
                                        <li class="breadcrumb-item">
                                            <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                        </div>

                        <div class="card-header border-0 pt-0">
                            <form method="GET" class="row g-2">
                                @if($currentFolder)
                                    <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
                                @endif
                                <div class="col-auto">
                                    <input type="text" name="search" class="form-control" placeholder="搜索文件..." value="{{ $search }}">
                                </div>
                                <div class="col-auto">
                                    <select name="type" class="form-select">
                                        <option value="">所有类型</option>
                                        <option value="image" {{ $type === 'image' ? 'selected' : '' }}>图片</option>
                                        <option value="video" {{ $type === 'video' ? 'selected' : '' }}>视频</option>
                                        <option value="audio" {{ $type === 'audio' ? 'selected' : '' }}>音频</option>
                                        <option value="application" {{ $type === 'application' ? 'selected' : '' }}>文档</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="bi bi-search"></i>
                                        搜索
                                    </button>
                                </div>
                                @if($search || $type)
                                    <div class="col-auto">
                                        <a href="{{ route('admin.file-manager.index', $currentFolder ? ['folder_id' => $currentFolder->id] : []) }}" class="btn btn-outline-secondary">
                                            <i class="bi bi-x"></i>
                                            清除
                                        </a>
                                    </div>
                                @endif
                            </form>
                        </div>

                        <div class="card-body">
                            @if($folders->count() > 0 || $files->count() > 0)
                                <div class="row row-cards">
                                    {{-- 文件夹 --}}
                                    @foreach($folders as $folder)
                                        <div class="col-6 col-sm-4 col-md-3 col-lg-2" data-folder-id="{{ $folder->id }}">
                                            <div class="card card-sm file-item">
                                                <div class="card-body p-2 text-center">
                                                    <div class="mb-2">
                                                        <i class="bi bi-folder text-blue" style="font-size: 2.5rem;"></i>
                                                    </div>
                                                    <div class="text-truncate" title="{{ $folder->name }}">
                                                        {{ $folder->name }}
                                                    </div>
                                                    <div class="text-secondary small">
                                                        {{ $folder->files_count }} 个文件
                                                    </div>
                                                    <div class="btn-group-vertical mt-2 w-100">
                                                        <a href="{{ route('admin.file-manager.index', ['folder_id' => $folder->id]) }}" class="btn btn-sm btn-outline-primary">
                                                            打开
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteFolder({{ $folder->id }})">
                                                            删除
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- 文件 --}}
                                    @foreach($files as $file)
                                        <div class="col-6 col-sm-4 col-md-3 col-lg-2" data-file-id="{{ $file->id }}">
                                            <div class="card card-sm file-item">
                                                <div class="card-body p-2 text-center">
                                                    <div class="mb-2">
                                                        @if($file->isImage())
                                                            <img src="{{ $file->url }}" alt="{{ $file->original_name }}" 
                                                                 class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                        @else
                                                            <i class="bi bi-{{ $file->type_icon }} text-blue" style="font-size: 2.5rem;"></i>
                                                        @endif
                                                    </div>
                                                    <div class="text-truncate" title="{{ $file->original_name }}">
                                                        {{ $file->original_name }}
                                                    </div>
                                                    <div class="text-secondary small">
                                                        {{ $file->human_size }}
                                                    </div>
                                                    <div class="btn-group-vertical mt-2 w-100">
                                                        <a href="{{ route('admin.file-manager.show', $file) }}" class="btn btn-sm btn-outline-primary">
                                                            详情
                                                        </a>
                                                        <a href="{{ $file->url }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                            预览
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteFile({{ $file->id }})">
                                                            删除
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="d-flex justify-content-center mt-4">
                                    {{ $files->withQueryString()->links() }}
                                </div>
                            @else
                                <div class="empty">
                                    <div class="empty-img">
                                        <img src="/static/illustrations/undraw_folder_files_re_pfj5.svg" height="128" alt="">
                                    </div>
                                    <p class="empty-title">此文件夹是空的</p>
                                    <p class="empty-subtitle text-secondary">
                                        点击上传文件或创建新文件夹来开始使用
                                    </p>
                                    <div class="empty-action">
                                        <button type="button" class="btn btn-primary" onclick="document.getElementById('fileUpload').click()">
                                            <i class="bi bi-upload"></i>
                                            上传文件
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 创建文件夹模态框 --}}
<div class="modal modal-blur fade" id="createFolderModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新建文件夹</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createFolderForm">
                <div class="modal-body">
                    @if($currentFolder)
                        <input type="hidden" name="parent_id" value="{{ $currentFolder->id }}">
                    @endif
                    <div class="mb-3">
                        <label class="form-label">文件夹名称</label>
                        <input type="text" class="form-control" name="name" placeholder="输入文件夹名称..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述（可选）</label>
                        <textarea class="form-control" name="description" placeholder="文件夹描述..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">创建</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 上传进度模态框 --}}
<div class="modal modal-blur fade" id="uploadModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">文件上传</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="uploadProgress"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.file-item {
    transition: transform 0.2s ease-in-out;
}

.file-item:hover {
    transform: translateY(-2px);
}

.breadcrumb-arrows .breadcrumb-item + .breadcrumb-item::before {
    content: '>';
    color: var(--tblr-secondary);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileUpload = document.getElementById('fileUpload');
    const createFolderForm = document.getElementById('createFolderForm');
    const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
    const createFolderModal = new bootstrap.Modal(document.getElementById('createFolderModal'));

    // 文件上传
    fileUpload.addEventListener('change', function() {
        if (this.files.length > 0) {
            uploadFiles(this.files);
        }
    });

    // 创建文件夹
    createFolderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("admin.file-manager.create-folder") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                createFolderModal.hide();
                location.reload();
            } else {
                alert(data.message || '创建文件夹失败');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('创建文件夹失败');
        });
    });

    function uploadFiles(files) {
        const formData = new FormData();
        
        for (let file of files) {
            formData.append('files[]', file);
        }
        
        @if($currentFolder)
            formData.append('folder_id', '{{ $currentFolder->id }}');
        @endif

        const progressContainer = document.getElementById('uploadProgress');
        progressContainer.innerHTML = `
            <div class="progress mb-3">
                <div class="progress-bar progress-bar-indeterminate"></div>
            </div>
            <p class="text-center mb-0">正在上传文件...</p>
        `;
        
        uploadModal.show();

        fetch('{{ route("admin.file-manager.upload") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                progressContainer.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <div class="d-flex">
                            <div class="alert-icon">
                                <i class="bi bi-check"></i>
                            </div>
                            <div>
                                <h4 class="alert-title">上传成功!</h4>
                                <div class="text-secondary">${data.message}</div>
                            </div>
                        </div>
                    </div>
                `;
                
                setTimeout(() => {
                    uploadModal.hide();
                    location.reload();
                }, 2000);
            } else {
                let errorHtml = '<div class="alert alert-danger mb-0">';
                errorHtml += '<div class="alert-title">上传失败</div>';
                if (data.errors && data.errors.length > 0) {
                    errorHtml += '<ul class="mb-0">';
                    data.errors.forEach(error => {
                        errorHtml += `<li>${error}</li>`;
                    });
                    errorHtml += '</ul>';
                }
                errorHtml += '</div>';
                progressContainer.innerHTML = errorHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            progressContainer.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <div class="alert-title">上传失败</div>
                    <div class="text-secondary">网络错误，请重试</div>
                </div>
            `;
        });
    }
});

function deleteFile(fileId) {
    if (confirm('确定要删除这个文件吗？此操作无法撤销。')) {
        fetch(`/admin/file-manager/files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '删除失败');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('删除失败');
        });
    }
}

function deleteFolder(folderId) {
    if (confirm('确定要删除这个文件夹吗？此操作无法撤销。')) {
        fetch(`/admin/file-manager/folders/${folderId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '删除失败');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('删除失败');
        });
    }
}
</script>
@endpush