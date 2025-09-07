<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileManagerController extends Controller
{
    public function index(Request $request)
    {
        $folderId = $request->get('folder_id');
        $search = $request->get('search');
        $type = $request->get('type');

        $currentFolder = null;
        if ($folderId) {
            $currentFolder = Folder::find($folderId);
            if (!$currentFolder) {
                return back()->with('error', '文件夹不存在');
            }
        }

        $foldersQuery = Folder::with(['creator', 'children', 'files'])
            ->where('parent_id', $folderId);

        $filesQuery = File::with(['folder', 'uploader'])
            ->where('folder_id', $folderId);

        if ($search) {
            $foldersQuery->where('name', 'like', "%{$search}%");
            $filesQuery->where(function ($query) use ($search) {
                $query->where('original_name', 'like', "%{$search}%")
                      ->orWhere('filename', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $filesQuery->where('mime_type', 'like', "{$type}/%");
        }

        $folders = $foldersQuery->orderBy('sort_order')->orderBy('name')->get();
        $files = $filesQuery->orderBy('created_at', 'desc')->paginate(20);

        $breadcrumb = $this->getBreadcrumb($currentFolder);

        return view('admin.file-manager.index', compact(
            'folders', 'files', 'currentFolder', 'breadcrumb', 'search', 'type'
        ));
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required',
            'files.*' => 'file|max:10240',
            'folder_id' => 'nullable|exists:gei5_folders,id'
        ]);

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $uploadedFile) {
            try {
                $filename = Str::uuid() . '.' . $uploadedFile->getClientOriginalExtension();
                $path = $uploadedFile->storeAs('uploads', $filename, 'public');

                $file = File::create([
                    'filename' => $filename,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getMimeType(),
                    'size' => $uploadedFile->getSize(),
                    'path' => $path,
                    'extension' => $uploadedFile->getClientOriginalExtension(),
                    'folder_id' => $request->get('folder_id'),
                    'uploaded_by' => auth('admin')->id(),
                ]);

                $uploadedFiles[] = $file;
            } catch (\Exception $e) {
                $errors[] = $uploadedFile->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => count($uploadedFiles) > 0,
            'files' => $uploadedFiles,
            'errors' => $errors,
            'message' => count($uploadedFiles) > 0 
                ? '成功上传 ' . count($uploadedFiles) . ' 个文件' 
                : '文件上传失败'
        ]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:gei5_folders,id',
            'description' => 'nullable|string'
        ]);

        $slug = Str::slug($request->name);
        
        $exists = Folder::where('parent_id', $request->parent_id)
                       ->where('slug', $slug)
                       ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['此文件夹名称已存在']
            ]);
        }

        $folder = Folder::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'created_by' => auth('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'folder' => $folder->load('creator'),
            'message' => '文件夹创建成功'
        ]);
    }

    public function show(File $file)
    {
        $file->load(['folder', 'uploader']);
        
        return view('admin.file-manager.show', compact('file'));
    }

    public function edit(File $file)
    {
        $file->load(['folder']);
        $folders = Folder::orderBy('name')->get();
        
        return view('admin.file-manager.edit', compact('file', 'folders'));
    }

    public function update(Request $request, File $file)
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'folder_id' => 'nullable|exists:gei5_folders,id'
        ]);

        $file->update($request->only('alt_text', 'description', 'folder_id'));

        return back()->with('success', '文件信息更新成功');
    }

    public function destroy(File $file): JsonResponse
    {
        try {
            $file->delete();
            
            return response()->json([
                'success' => true,
                'message' => '文件删除成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '文件删除失败: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyFolder(Folder $folder): JsonResponse
    {
        try {
            if ($folder->files()->count() > 0 || $folder->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => '文件夹不为空，无法删除'
                ], 400);
            }

            $folder->delete();
            
            return response()->json([
                'success' => true,
                'message' => '文件夹删除成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '文件夹删除失败: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete,move',
            'file_ids' => 'array',
            'file_ids.*' => 'exists:gei5_files,id',
            'folder_ids' => 'array',
            'folder_ids.*' => 'exists:gei5_folders,id',
            'target_folder_id' => 'nullable|exists:gei5_folders,id'
        ]);

        $fileIds = $request->get('file_ids', []);
        $folderIds = $request->get('folder_ids', []);
        $action = $request->get('action');

        try {
            switch ($action) {
                case 'delete':
                    File::whereIn('id', $fileIds)->delete();
                    Folder::whereIn('id', $folderIds)->delete();
                    $message = '选中项目删除成功';
                    break;

                case 'move':
                    if ($fileIds) {
                        File::whereIn('id', $fileIds)->update([
                            'folder_id' => $request->get('target_folder_id')
                        ]);
                    }
                    if ($folderIds) {
                        Folder::whereIn('id', $folderIds)->update([
                            'parent_id' => $request->get('target_folder_id')
                        ]);
                    }
                    $message = '选中项目移动成功';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量操作失败: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getBreadcrumb(?Folder $folder): array
    {
        $breadcrumb = [
            ['name' => '文件管理', 'url' => route('admin.file-manager.index')]
        ];

        if ($folder) {
            $path = [];
            $current = $folder;
            
            while ($current) {
                array_unshift($path, $current);
                $current = $current->parent;
            }

            foreach ($path as $item) {
                $breadcrumb[] = [
                    'name' => $item->name,
                    'url' => route('admin.file-manager.index', ['folder_id' => $item->id])
                ];
            }
        }

        return $breadcrumb;
    }
}
