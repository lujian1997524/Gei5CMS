<?php

namespace App\Http\Controllers\Api;

use App\Models\Plugin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class PluginController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Plugin::query();

            // 应用过滤器
            $query = $this->applyFilters($query, $request, [
                'status' => 'exact',
                'name' => 'like',
                'author' => 'like',
                'is_system' => 'exact',
            ]);

            // 应用排序
            $query = $this->applySorting($query, $request, [
                'id', 'name', 'status', 'priority', 'version', 'created_at', 'updated_at'
            ]);

            // 返回分页结果
            return $this->paginatedResponse($query, $request, 'Plugins retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve plugins: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();

            if (!$plugin) {
                return $this->notFoundResponse('Plugin');
            }

            $pluginData = [
                'id' => $plugin->id,
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'description' => $plugin->description,
                'version' => $plugin->version,
                'author' => $plugin->author,
                'status' => $plugin->status,
                'priority' => $plugin->priority,
                'is_system' => $plugin->is_system,
                'config' => $plugin->config,
                'metadata' => $plugin->metadata,
                'created_at' => $plugin->created_at,
                'updated_at' => $plugin->updated_at,
                'installed_at' => $plugin->installed_at,
                'last_activated_at' => $plugin->last_activated_at,
            ];

            // 获取插件配置信息
            if ($plugin->config_file && Storage::exists($plugin->config_file)) {
                $configContent = Storage::get($plugin->config_file);
                $pluginData['config_content'] = $configContent;
            }

            return $this->successResponse($pluginData, 'Plugin details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve plugin: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateRequest($request, [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:100|unique:gei5_plugins,slug',
                'description' => 'nullable|string',
                'version' => 'required|string|max:50',
                'author' => 'required|string|max:255',
                'priority' => 'sometimes|integer|min:0',
                'config' => 'sometimes|array',
                'metadata' => 'sometimes|array',
                'plugin_file' => 'sometimes|file|mimes:zip|max:10240', // 10MB
            ]);

            // 创建插件记录
            $plugin = Plugin::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'version' => $data['version'],
                'author' => $data['author'],
                'status' => 'inactive',
                'priority' => $data['priority'] ?? 100,
                'is_system' => false,
                'config' => $data['config'] ?? [],
                'metadata' => $data['metadata'] ?? [],
                'installed_at' => now(),
            ]);

            // 处理插件文件上传
            if ($request->hasFile('plugin_file')) {
                $uploadedFile = $request->file('plugin_file');
                $filename = $data['slug'] . '_' . time() . '.zip';
                $path = $uploadedFile->storeAs('plugins', $filename, 'local');
                
                $plugin->update([
                    'plugin_file' => $path,
                    'file_path' => storage_path('app/' . $path),
                ]);
            }

            do_action('plugin.created', $plugin, $request);

            return $this->successResponse([
                'plugin' => $this->transformResource($plugin),
            ], 'Plugin created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create plugin: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();

            if (!$plugin) {
                return $this->notFoundResponse('Plugin');
            }

            // 系统插件不允许修改基础信息
            if ($plugin->is_system && $request->hasAny(['name', 'slug', 'version', 'author'])) {
                return $this->forbiddenResponse('System plugins cannot be modified');
            }

            $rules = [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'version' => 'sometimes|string|max:50',
                'author' => 'sometimes|string|max:255',
                'priority' => 'sometimes|integer|min:0',
                'config' => 'sometimes|array',
                'metadata' => 'sometimes|array',
            ];

            // 如果修改slug，需要验证唯一性
            if ($request->has('slug') && $request->slug !== $plugin->slug) {
                $rules['slug'] = 'required|string|max:100|unique:gei5_plugins,slug,' . $plugin->id;
            }

            $data = $this->validateRequest($request, $rules);

            $plugin->update($data);

            do_action('plugin.updated', $plugin, $request);

            return $this->successResponse([
                'plugin' => $this->transformResource($plugin),
            ], 'Plugin updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update plugin: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $slug): JsonResponse
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();

            if (!$plugin) {
                return $this->notFoundResponse('Plugin');
            }

            // 系统插件不允许删除
            if ($plugin->is_system) {
                return $this->forbiddenResponse('System plugins cannot be deleted');
            }

            // 如果插件处于激活状态，先停用
            if ($plugin->status === 'active') {
                $plugin->update(['status' => 'inactive']);
                do_action('plugin.deactivated', $plugin);
            }

            // 删除插件文件
            if ($plugin->plugin_file && Storage::exists($plugin->plugin_file)) {
                Storage::delete($plugin->plugin_file);
            }

            // 删除插件配置文件
            if ($plugin->config_file && Storage::exists($plugin->config_file)) {
                Storage::delete($plugin->config_file);
            }

            do_action('plugin.deleting', $plugin);

            $plugin->delete();

            do_action('plugin.deleted', $slug);

            return $this->successResponse(null, 'Plugin deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete plugin: ' . $e->getMessage(), 500);
        }
    }

    public function activate(string $slug): JsonResponse
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();

            if (!$plugin) {
                return $this->notFoundResponse('Plugin');
            }

            if ($plugin->status === 'active') {
                return $this->errorResponse('Plugin is already active', 400);
            }

            $plugin->update([
                'status' => 'active',
                'last_activated_at' => now(),
            ]);

            do_action('plugin.activated', $plugin);

            return $this->successResponse([
                'plugin' => $this->transformResource($plugin),
            ], 'Plugin activated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate plugin: ' . $e->getMessage(), 500);
        }
    }

    public function deactivate(string $slug): JsonResponse
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();

            if (!$plugin) {
                return $this->notFoundResponse('Plugin');
            }

            if ($plugin->status === 'inactive') {
                return $this->errorResponse('Plugin is already inactive', 400);
            }

            // 系统插件不允许停用
            if ($plugin->is_system) {
                return $this->forbiddenResponse('System plugins cannot be deactivated');
            }

            $plugin->update(['status' => 'inactive']);

            do_action('plugin.deactivated', $plugin);

            return $this->successResponse([
                'plugin' => $this->transformResource($plugin),
            ], 'Plugin deactivated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to deactivate plugin: ' . $e->getMessage(), 500);
        }
    }

    public function updatePriority(Request $request, string $slug): JsonResponse
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();

            if (!$plugin) {
                return $this->notFoundResponse('Plugin');
            }

            $data = $this->validateRequest($request, [
                'priority' => 'required|integer|min:0|max:999',
            ]);

            $plugin->update(['priority' => $data['priority']]);

            do_action('plugin.priority_updated', $plugin);

            return $this->successResponse([
                'plugin' => $this->transformResource($plugin),
            ], 'Plugin priority updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update plugin priority: ' . $e->getMessage(), 500);
        }
    }
}