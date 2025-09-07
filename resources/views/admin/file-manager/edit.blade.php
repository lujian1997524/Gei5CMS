@extends('admin.layouts.app')

@section('title', '编辑文件信息')

@section('content')
<div class="page-wrapper">
    <div class="page-body">
        <div class="container-fluid">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">编辑文件信息</h3>
                        </div>
                        <form action="{{ route('admin.file-manager.update', $file) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">文件名</label>
                                            <input type="text" class="form-control" value="{{ $file->original_name }}" readonly>
                                            <small class="form-hint">文件名无法修改</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">所在文件夹</label>
                                            <select class="form-select" name="folder_id">
                                                <option value="">根目录</option>
                                                @foreach($folders as $folder)
                                                    <option value="{{ $folder->id }}" {{ $file->folder_id == $folder->id ? 'selected' : '' }}>
                                                        {{ $folder->path }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                @if($file->isImage())
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">替代文本 (Alt Text)</label>
                                                <input type="text" class="form-control @error('alt_text') is-invalid @enderror" 
                                                       name="alt_text" value="{{ old('alt_text', $file->alt_text) }}" 
                                                       placeholder="为图片添加描述性文本，有助于SEO和无障碍访问">
                                                @error('alt_text')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-hint">用于屏幕阅读器和搜索引擎优化</small>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">文件描述</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                                      name="description" rows="4" 
                                                      placeholder="添加文件描述...">{{ old('description', $file->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-hint">可选的文件描述信息</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer text-end">
                                <div class="d-flex">
                                    <a href="{{ route('admin.file-manager.show', $file) }}" class="btn btn-link">取消</a>
                                    <button type="submit" class="btn btn-primary ms-auto">
                                        <i class="bi bi-check"></i>
                                        保存更改
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">文件预览</h3>
                        </div>
                        <div class="card-body text-center">
                            @if($file->isImage())
                                <img src="{{ $file->url }}" alt="{{ $file->original_name }}" 
                                     class="img-fluid rounded" style="max-height: 200px;">
                            @elseif($file->isVideo())
                                <video class="img-fluid rounded" style="max-height: 200px;">
                                    <source src="{{ $file->url }}" type="{{ $file->mime_type }}">
                                </video>
                            @else
                                <div class="mb-3">
                                    <i class="bi bi-{{ $file->type_icon }} text-blue" style="font-size: 3rem;"></i>
                                </div>
                                <p class="text-truncate">{{ $file->original_name }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">文件信息</h3>
                        </div>
                        <div class="card-body">
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">文件大小</div>
                                    <div class="datagrid-content">{{ $file->human_size }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">文件类型</div>
                                    <div class="datagrid-content">{{ $file->mime_type }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">上传时间</div>
                                    <div class="datagrid-content">{{ $file->created_at->format('Y-m-d H:i:s') }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">上传者</div>
                                    <div class="datagrid-content">{{ $file->uploader->username ?? $file->uploader->email }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection