@extends('admin.layouts.app')

@section('title', '文件详情')

@section('content')
<div class="page-wrapper">
    <div class="page-body">
        <div class="container-fluid">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">文件预览</h3>
                        </div>
                        <div class="card-body text-center">
                            @if($file->isImage())
                                <img src="{{ $file->url }}" alt="{{ $file->original_name }}" 
                                     class="img-fluid rounded" style="max-height: 400px;">
                            @elseif($file->isVideo())
                                <video controls class="img-fluid rounded" style="max-height: 400px;">
                                    <source src="{{ $file->url }}" type="{{ $file->mime_type }}">
                                    您的浏览器不支持视频播放。
                                </video>
                            @elseif($file->isAudio())
                                <div class="mb-3">
                                    <i class="bi bi-file-music text-blue" style="font-size: 4rem;"></i>
                                </div>
                                <audio controls>
                                    <source src="{{ $file->url }}" type="{{ $file->mime_type }}">
                                    您的浏览器不支持音频播放。
                                </audio>
                            @else
                                <div class="empty">
                                    <div class="empty-img">
                                        <i class="bi bi-{{ $file->type_icon }} text-blue" style="font-size: 4rem;"></i>
                                    </div>
                                    <p class="empty-title">{{ $file->original_name }}</p>
                                    <p class="empty-subtitle text-secondary">
                                        无法预览此文件类型
                                    </p>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer">
                            <div class="btn-list justify-content-center">
                                <a href="{{ $file->url }}" class="btn btn-primary" download>
                                    <i class="bi bi-download"></i>
                                    下载文件
                                </a>
                                <a href="{{ $file->url }}" target="_blank" class="btn btn-outline-primary">
                                    <i class="bi bi-external-link"></i>
                                    在新窗口打开
                                </a>
                                <a href="{{ route('admin.file-manager.edit', $file) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-edit"></i>
                                    编辑信息
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">文件信息</h3>
                        </div>
                        <div class="card-body">
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">文件名</div>
                                    <div class="datagrid-content">{{ $file->original_name }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">文件大小</div>
                                    <div class="datagrid-content">{{ $file->human_size }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">文件类型</div>
                                    <div class="datagrid-content">{{ $file->mime_type }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">扩展名</div>
                                    <div class="datagrid-content">.{{ $file->extension }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">所在文件夹</div>
                                    <div class="datagrid-content">
                                        @if($file->folder)
                                            <a href="{{ route('admin.file-manager.index', ['folder_id' => $file->folder->id]) }}">
                                                {{ $file->folder->name }}
                                            </a>
                                        @else
                                            根目录
                                        @endif
                                    </div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">上传者</div>
                                    <div class="datagrid-content">{{ $file->uploader->username ?? $file->uploader->email }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">上传时间</div>
                                    <div class="datagrid-content">{{ $file->created_at->format('Y-m-d H:i:s') }}</div>
                                </div>
                                @if($file->alt_text)
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">替代文本</div>
                                        <div class="datagrid-content">{{ $file->alt_text }}</div>
                                    </div>
                                @endif
                                @if($file->description)
                                    <div class="datagrid-item">
                                        <div class="datagrid-title">描述</div>
                                        <div class="datagrid-content">{{ $file->description }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($file->isImage() && $file->metadata)
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">图片信息</h3>
                            </div>
                            <div class="card-body">
                                <div class="datagrid">
                                    @if(isset($file->metadata['width']))
                                        <div class="datagrid-item">
                                            <div class="datagrid-title">尺寸</div>
                                            <div class="datagrid-content">{{ $file->metadata['width'] }} × {{ $file->metadata['height'] }} 像素</div>
                                        </div>
                                    @endif
                                    @foreach($file->metadata as $key => $value)
                                        @if(!in_array($key, ['width', 'height']) && is_string($value))
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">{{ ucfirst($key) }}</div>
                                                <div class="datagrid-content">{{ $value }}</div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">操作</h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-list">
                                <button type="button" class="btn btn-outline-info" onclick="copyUrl()">
                                    <i class="bi bi-copy"></i>
                                    复制链接
                                </button>
                                <a href="{{ route('admin.file-manager.edit', $file) }}" class="btn btn-outline-warning">
                                    <i class="bi bi-edit"></i>
                                    编辑信息
                                </a>
                                <button type="button" class="btn btn-outline-danger" onclick="deleteFile()">
                                    <i class="bi bi-trash"></i>
                                    删除文件
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.file-manager.index', $file->folder ? ['folder_id' => $file->folder->id] : []) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i>
                            返回文件管理
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyUrl() {
    navigator.clipboard.writeText('{{ $file->url }}').then(function() {
        alert('文件链接已复制到剪贴板');
    }, function(err) {
        console.error('Could not copy text: ', err);
        alert('复制失败，请手动复制链接');
    });
}

function deleteFile() {
    if (confirm('确定要删除这个文件吗？此操作无法撤销。')) {
        fetch('{{ route("admin.file-manager.destroy", $file) }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("admin.file-manager.index", $file->folder ? ["folder_id" => $file->folder->id] : []) }}';
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