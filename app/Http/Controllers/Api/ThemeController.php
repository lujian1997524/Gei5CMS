<?php

namespace App\Http\Controllers\Api;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class ThemeController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Theme::query();

            // 应用过滤器
            $query = $this->applyFilters($query, $request, [
                'status' => 'exact',
                'name' => 'like',
                'author' => 'like',
                'is_system' => 'exact',
                'is_mobile_compatible' => 'exact',
            ]);

            // 应用排序
            $query = $this->applySorting($query, $request, [
                'id', 'name', 'status', 'version', 'created_at', 'updated_at'
            ]);

            // 返回分页结果
            return $this->paginatedResponse($query, $request, 'Themes retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve themes: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $theme = Theme::where('slug', $slug)->first();

            if (!$theme) {
                return $this->notFoundResponse('Theme');
            }

            $themeData = [
                'id' => $theme->id,
                'name' => $theme->name,
                'slug' => $theme->slug,
                'description' => $theme->description,
                'version' => $theme->version,
                'author' => $theme->author,
                'author_email' => $theme->author_email,
                'website' => $theme->website,
                'status' => $theme->status,
                'is_system' => $theme->is_system,
                'is_mobile_compatible' => $theme->is_mobile_compatible,
                'supports_dark_mode' => $theme->supports_dark_mode,
                'config' => $theme->config,
                'metadata' => $theme->metadata,
                'screenshot' => $theme->screenshot,
                'created_at' => $theme->created_at,
                'updated_at' => $theme->updated_at,
                'installed_at' => $theme->installed_at,
                'activated_at' => $theme->activated_at,
            ];

            // 获取主题配置信息
            if ($theme->config_file && Storage::exists($theme->config_file)) {
                $configContent = Storage::get($theme->config_file);
                $themeData['config_content'] = $configContent;
            }

            // 获取主题截图URL
            if ($theme->screenshot) {
                $themeData['screenshot_url'] = asset($theme->screenshot);
            }

            return $this->successResponse($themeData, 'Theme details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve theme: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateRequest($request, [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:100|unique:gei5_themes,slug',
                'description' => 'nullable|string',
                'version' => 'required|string|max:50',
                'author' => 'required|string|max:255',
                'author_email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
                'is_mobile_compatible' => 'sometimes|boolean',
                'supports_dark_mode' => 'sometimes|boolean',
                'config' => 'sometimes|array',
                'metadata' => 'sometimes|array',
                'theme_file' => 'sometimes|file|mimes:zip|max:20480', // 20MB
                'screenshot' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB
            ]);

            // 创建主题记录
            $theme = Theme::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'version' => $data['version'],
                'author' => $data['author'],
                'author_email' => $data['author_email'] ?? null,
                'website' => $data['website'] ?? null,
                'status' => 'inactive',
                'is_system' => false,
                'is_mobile_compatible' => $data['is_mobile_compatible'] ?? true,
                'supports_dark_mode' => $data['supports_dark_mode'] ?? false,
                'config' => $data['config'] ?? [],
                'metadata' => $data['metadata'] ?? [],
                'installed_at' => now(),
            ]);

            // 处理主题文件上传
            if ($request->hasFile('theme_file')) {
                $uploadedFile = $request->file('theme_file');
                $filename = $data['slug'] . '_' . time() . '.zip';
                $path = $uploadedFile->storeAs('themes', $filename, 'local');
                
                $theme->update([
                    'theme_file' => $path,
                    'file_path' => storage_path('app/' . $path),
                ]);
            }

            // 处理截图上传
            if ($request->hasFile('screenshot')) {
                $screenshot = $request->file('screenshot');
                $filename = $data['slug'] . '_screenshot_' . time() . '.' . $screenshot->getClientOriginalExtension();
                $path = $screenshot->storeAs('themes/screenshots', $filename, 'public');
                
                $theme->update(['screenshot' => 'storage/' . $path]);
            }

            do_action('theme.created', $theme, $request);

            return $this->successResponse([
                'theme' => $this->transformResource($theme),
            ], 'Theme created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create theme: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        try {
            $theme = Theme::where('slug', $slug)->first();

            if (!$theme) {
                return $this->notFoundResponse('Theme');
            }

            // 系统主题不允许修改基础信息
            if ($theme->is_system && $request->hasAny(['name', 'slug', 'version', 'author'])) {
                return $this->forbiddenResponse('System themes cannot be modified');
            }

            $rules = [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'version' => 'sometimes|string|max:50',
                'author' => 'sometimes|string|max:255',
                'author_email' => 'sometimes|email|max:255',
                'website' => 'sometimes|url|max:255',
                'is_mobile_compatible' => 'sometimes|boolean',
                'supports_dark_mode' => 'sometimes|boolean',
                'config' => 'sometimes|array',
                'metadata' => 'sometimes|array',
                'screenshot' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];

            // 如果修改slug，需要验证唯一性
            if ($request->has('slug') && $request->slug !== $theme->slug) {
                $rules['slug'] = 'required|string|max:100|unique:gei5_themes,slug,' . $theme->id;
            }

            $data = $this->validateRequest($request, $rules);

            // 处理截图更新
            if ($request->hasFile('screenshot')) {
                // 删除旧截图
                if ($theme->screenshot && File::exists(public_path($theme->screenshot))) {
                    File::delete(public_path($theme->screenshot));
                }

                $screenshot = $request->file('screenshot');
                $filename = $theme->slug . '_screenshot_' . time() . '.' . $screenshot->getClientOriginalExtension();
                $path = $screenshot->storeAs('themes/screenshots', $filename, 'public');
                $data['screenshot'] = 'storage/' . $path;
            }

            $theme->update($data);

            do_action('theme.updated', $theme, $request);

            return $this->successResponse([
                'theme' => $this->transformResource($theme),
            ], 'Theme updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update theme: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $slug): JsonResponse
    {
        try {
            $theme = Theme::where('slug', $slug)->first();

            if (!$theme) {
                return $this->notFoundResponse('Theme');
            }

            // 系统主题不允许删除
            if ($theme->is_system) {
                return $this->forbiddenResponse('System themes cannot be deleted');
            }

            // 激活状态的主题不允许删除
            if ($theme->status === 'active') {
                return $this->forbiddenResponse('Active theme cannot be deleted. Please activate another theme first.');
            }

            // 删除主题文件
            if ($theme->theme_file && Storage::exists($theme->theme_file)) {
                Storage::delete($theme->theme_file);
            }

            // 删除主题配置文件
            if ($theme->config_file && Storage::exists($theme->config_file)) {
                Storage::delete($theme->config_file);
            }

            // 删除截图文件
            if ($theme->screenshot && File::exists(public_path($theme->screenshot))) {
                File::delete(public_path($theme->screenshot));
            }

            do_action('theme.deleting', $theme);

            $theme->delete();

            do_action('theme.deleted', $slug);

            return $this->successResponse(null, 'Theme deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete theme: ' . $e->getMessage(), 500);
        }
    }

    public function activate(string $slug): JsonResponse
    {
        try {
            $theme = Theme::where('slug', $slug)->first();

            if (!$theme) {
                return $this->notFoundResponse('Theme');
            }

            if ($theme->status === 'active') {
                return $this->errorResponse('Theme is already active', 400);
            }

            // 停用当前活跃主题（单主题模式）
            Theme::where('status', 'active')->update([
                'status' => 'inactive'
            ]);

            // 激活新主题
            $theme->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

            do_action('theme.activated', $theme);

            return $this->successResponse([
                'theme' => $this->transformResource($theme),
            ], 'Theme activated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate theme: ' . $e->getMessage(), 500);
        }
    }

    public function deactivate(string $slug): JsonResponse
    {
        try {
            $theme = Theme::where('slug', $slug)->first();

            if (!$theme) {
                return $this->notFoundResponse('Theme');
            }

            if ($theme->status === 'inactive') {
                return $this->errorResponse('Theme is already inactive', 400);
            }

            // 系统主题不允许停用
            if ($theme->is_system) {
                return $this->forbiddenResponse('System themes cannot be deactivated');
            }

            // 不允许停用唯一活跃的主题
            $activeThemeCount = Theme::where('status', 'active')->count();
            if ($activeThemeCount <= 1) {
                return $this->forbiddenResponse('Cannot deactivate the only active theme. Please activate another theme first.');
            }

            $theme->update(['status' => 'inactive']);

            do_action('theme.deactivated', $theme);

            return $this->successResponse([
                'theme' => $this->transformResource($theme),
            ], 'Theme deactivated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to deactivate theme: ' . $e->getMessage(), 500);
        }
    }
}