<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class SettingController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Setting::query();

            // 应用过滤器
            $query = $this->applyFilters($query, $request, [
                'group_name' => 'exact',
                'key' => 'like',
                'is_system' => 'exact',
                'is_public' => 'exact',
            ]);

            // 应用排序
            $query = $this->applySorting($query, $request, [
                'id', 'key', 'group_name', 'created_at', 'updated_at'
            ]);

            // 返回分页结果
            return $this->paginatedResponse($query, $request, 'Settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve settings: ' . $e->getMessage(), 500);
        }
    }

    public function group(string $groupName): JsonResponse
    {
        try {
            $settings = Setting::where('group_name', $groupName)->get();

            if ($settings->isEmpty()) {
                return $this->notFoundResponse('Settings group');
            }

            // 将设置转换为键值对格式
            $settingsData = [];
            foreach ($settings as $setting) {
                $settingsData[$setting->key] = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'is_system' => $setting->is_system,
                    'is_public' => $setting->is_public,
                    'validation_rules' => $setting->validation_rules,
                    'options' => $setting->options,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
            }

            return $this->successResponse([
                'group_name' => $groupName,
                'settings' => $settingsData,
                'count' => count($settingsData),
            ], 'Settings group retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve settings group: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateRequest($request, [
                'key' => 'required|string|max:100|unique:gei5_settings,key',
                'value' => 'required',
                'type' => 'required|in:string,integer,float,boolean,json,array',
                'group_name' => 'required|string|max:50',
                'description' => 'nullable|string',
                'is_system' => 'sometimes|boolean',
                'is_public' => 'sometimes|boolean',
                'validation_rules' => 'nullable|string',
                'options' => 'nullable|array',
            ]);

            // 验证值的类型
            $this->validateSettingValue($data['value'], $data['type']);

            // 只有超级管理员才能创建系统设置
            if (isset($data['is_system']) && $data['is_system'] && !auth()->user()->is_super_admin) {
                return $this->forbiddenResponse('Only super administrators can create system settings');
            }

            $setting = Setting::create([
                'key' => $data['key'],
                'value' => $this->formatSettingValue($data['value'], $data['type']),
                'type' => $data['type'],
                'group_name' => $data['group_name'],
                'description' => $data['description'] ?? '',
                'is_system' => $data['is_system'] ?? false,
                'is_public' => $data['is_public'] ?? false,
                'validation_rules' => $data['validation_rules'] ?? null,
                'options' => $data['options'] ?? null,
            ]);

            // 清除设置缓存
            $this->clearSettingsCache($data['group_name'], $data['key']);

            do_action('setting.created', $setting, $request);

            return $this->successResponse([
                'setting' => $this->transformResource($setting),
            ], 'Setting created successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create setting: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return $this->notFoundResponse('Setting');
            }

            // 系统设置需要超级管理员权限才能修改
            if ($setting->is_system && !auth()->user()->is_super_admin) {
                return $this->forbiddenResponse('Only super administrators can modify system settings');
            }

            $rules = [
                'value' => 'required',
                'description' => 'sometimes|string',
                'validation_rules' => 'sometimes|string',
                'options' => 'sometimes|array',
            ];

            // 非系统设置允许修改更多字段
            if (!$setting->is_system) {
                $rules = array_merge($rules, [
                    'type' => 'sometimes|in:string,integer,float,boolean,json,array',
                    'group_name' => 'sometimes|string|max:50',
                    'is_public' => 'sometimes|boolean',
                ]);
            }

            $data = $this->validateRequest($request, $rules);

            // 如果提供了新类型，验证值是否匹配
            $type = $data['type'] ?? $setting->type;
            $this->validateSettingValue($data['value'], $type);

            // 更新设置
            $setting->update([
                'value' => $this->formatSettingValue($data['value'], $type),
                'type' => $type,
                'group_name' => $data['group_name'] ?? $setting->group_name,
                'description' => $data['description'] ?? $setting->description,
                'is_public' => $data['is_public'] ?? $setting->is_public,
                'validation_rules' => $data['validation_rules'] ?? $setting->validation_rules,
                'options' => $data['options'] ?? $setting->options,
            ]);

            // 清除设置缓存
            $this->clearSettingsCache($setting->group_name, $setting->key);

            do_action('setting.updated', $setting, $request);

            return $this->successResponse([
                'setting' => $this->transformResource($setting),
            ], 'Setting updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update setting: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $key): JsonResponse
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return $this->notFoundResponse('Setting');
            }

            // 系统设置不允许删除
            if ($setting->is_system) {
                return $this->forbiddenResponse('System settings cannot be deleted');
            }

            // 清除设置缓存
            $this->clearSettingsCache($setting->group_name, $setting->key);

            do_action('setting.deleting', $setting);

            $setting->delete();

            do_action('setting.deleted', $key);

            return $this->successResponse(null, 'Setting deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete setting: ' . $e->getMessage(), 500);
        }
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $data = $this->validateRequest($request, [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string|exists:gei5_settings,key',
                'settings.*.value' => 'required',
            ]);

            $updatedSettings = [];
            $errors = [];

            foreach ($data['settings'] as $settingData) {
                $setting = Setting::where('key', $settingData['key'])->first();
                
                if (!$setting) {
                    $errors[] = "Setting '{$settingData['key']}' not found";
                    continue;
                }

                // 检查权限
                if ($setting->is_system && !auth()->user()->is_super_admin) {
                    $errors[] = "Permission denied for system setting '{$settingData['key']}'";
                    continue;
                }

                try {
                    // 验证值类型
                    $this->validateSettingValue($settingData['value'], $setting->type);
                    
                    // 更新设置
                    $setting->update([
                        'value' => $this->formatSettingValue($settingData['value'], $setting->type)
                    ]);

                    $updatedSettings[] = $setting->key;

                    // 清除设置缓存
                    $this->clearSettingsCache($setting->group_name, $setting->key);

                } catch (\Exception $e) {
                    $errors[] = "Failed to update '{$settingData['key']}': " . $e->getMessage();
                }
            }

            do_action('settings.bulk_updated', $updatedSettings, $request);

            $response = [
                'updated_settings' => $updatedSettings,
                'updated_count' => count($updatedSettings),
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
                $response['error_count'] = count($errors);
            }

            $message = count($updatedSettings) > 0 ? 
                'Settings updated successfully' : 
                'No settings were updated';

            return $this->successResponse($response, $message);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to bulk update settings: ' . $e->getMessage(), 500);
        }
    }

    public function getPublicSettings(): JsonResponse
    {
        try {
            $settings = Setting::where('is_public', true)->get();

            $publicSettings = [];
            foreach ($settings as $setting) {
                $publicSettings[$setting->key] = $this->parseSettingValue($setting->value, $setting->type);
            }

            return $this->successResponse($publicSettings, 'Public settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve public settings: ' . $e->getMessage(), 500);
        }
    }

    public function clearCache(Request $request): JsonResponse
    {
        try {
            $data = $this->validateRequest($request, [
                'group_name' => 'nullable|string',
                'key' => 'nullable|string',
            ]);

            if (isset($data['key'])) {
                // 清除特定设置的缓存
                $setting = Setting::where('key', $data['key'])->first();
                if ($setting) {
                    $this->clearSettingsCache($setting->group_name, $data['key']);
                }
                $message = "Cache cleared for setting: {$data['key']}";
            } elseif (isset($data['group_name'])) {
                // 清除特定组的缓存
                Cache::forget("settings_group_{$data['group_name']}");
                $message = "Cache cleared for group: {$data['group_name']}";
            } else {
                // 清除所有设置缓存
                Cache::flush();
                $message = 'All settings cache cleared';
            }

            return $this->successResponse(null, $message);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to clear cache: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 验证设置值的类型
     */
    private function validateSettingValue($value, string $type): void
    {
        switch ($type) {
            case 'integer':
                if (!is_numeric($value) || !is_int($value + 0)) {
                    throw new \InvalidArgumentException('Value must be an integer');
                }
                break;
            case 'float':
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException('Value must be a number');
                }
                break;
            case 'boolean':
                if (!is_bool($value) && !in_array(strtolower($value), ['true', 'false', '1', '0'])) {
                    throw new \InvalidArgumentException('Value must be a boolean');
                }
                break;
            case 'json':
            case 'array':
                if (is_string($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \InvalidArgumentException('Value must be valid JSON');
                    }
                } elseif (!is_array($value)) {
                    throw new \InvalidArgumentException('Value must be an array or valid JSON string');
                }
                break;
        }
    }

    /**
     * 格式化设置值用于存储
     */
    private function formatSettingValue($value, string $type): string
    {
        switch ($type) {
            case 'integer':
                return (string) intval($value);
            case 'float':
                return (string) floatval($value);
            case 'boolean':
                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }
                return in_array(strtolower($value), ['true', '1']) ? '1' : '0';
            case 'json':
            case 'array':
                return is_string($value) ? $value : json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * 解析设置值
     */
    private function parseSettingValue(string $value, string $type)
    {
        switch ($type) {
            case 'integer':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'boolean':
                return $value === '1' || $value === 'true';
            case 'json':
            case 'array':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * 清除设置缓存
     */
    private function clearSettingsCache(?string $groupName = null, ?string $key = null): void
    {
        if ($key) {
            Cache::forget("setting_{$key}");
        }
        
        if ($groupName) {
            Cache::forget("settings_group_{$groupName}");
        }

        // 清除公共设置缓存
        Cache::forget('public_settings');
    }
}