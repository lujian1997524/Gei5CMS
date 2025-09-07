<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function __construct()
    {
        // 权限中间件已在路由中设置
    }

    /**
     * 设置首页 - 显示所有分组
     */
    public function index(Request $request)
    {
        do_action('admin.settings.loading');

        // 获取所有设置分组
        $groups = Setting::select('setting_group')
            ->groupBy('setting_group')
            ->orderBy('setting_group')
            ->pluck('setting_group');

        // 获取每个分组的设置数量和描述
        $groupsData = [];
        foreach ($groups as $group) {
            $count = Setting::byGroup($group)->count();
            $groupsData[] = [
                'name' => $group,
                'label' => $this->getGroupLabel($group),
                'description' => $this->getGroupDescription($group),
                'count' => $count,
                'icon' => $this->getGroupIcon($group),
            ];
        }

        // 如果没有设置，初始化默认设置
        if ($groups->isEmpty()) {
            $this->initializeDefaultSettings();
            return redirect()->route('admin.settings.index');
        }

        do_action('admin.settings.loaded', $groupsData);

        // 获取所有设置用于视图显示
        $allSettings = Setting::orderBy('setting_group')->orderBy('setting_key')->get()->groupBy('setting_group');

        return view('admin.settings.index', compact('groupsData', 'allSettings'));
    }

    /**
     * 显示特定分组的设置
     */
    public function group(Request $request, $group)
    {
        $settings = Setting::byGroup($group)
            ->orderBy('setting_key')
            ->get();

        if ($settings->isEmpty()) {
            return redirect()->route('admin.settings.index')
                ->with('error', '设置分组不存在');
        }

        $groupLabel = $this->getGroupLabel($group);
        $groupDescription = $this->getGroupDescription($group);

        return view('admin.settings.group', compact(
            'settings',
            'group',
            'groupLabel',
            'groupDescription'
        ));
    }

    /**
     * 创建新设置
     */
    public function create()
    {
        $groups = $this->getAvailableGroups();
        $types = $this->getAvailableTypes();

        return view('admin.settings.create', compact('groups', 'types'));
    }

    /**
     * 存储新设置
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'setting_key' => 'required|string|max:255|unique:settings,setting_key',
            'setting_value' => 'nullable',
            'setting_group' => 'required|string|max:100',
            'setting_type' => 'required|in:string,integer,boolean,json,text',
            'description' => 'nullable|string|max:500',
            'is_autoload' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // 根据类型处理值
        $value = $this->processSettingValue(
            $request->input('setting_value'),
            $request->input('setting_type')
        );

        $setting = Setting::create([
            'setting_key' => $request->input('setting_key'),
            'setting_value' => $value,
            'setting_group' => $request->input('setting_group'),
            'setting_type' => $request->input('setting_type'),
            'description' => $request->input('description'),
            'is_autoload' => $request->boolean('is_autoload'),
        ]);

        do_action('admin.setting.created', $setting);

        return redirect()->route('admin.settings.group', $setting->setting_group)
            ->with('success', '设置已创建');
    }

    /**
     * 编辑设置
     */
    public function edit(Setting $setting)
    {
        $groups = $this->getAvailableGroups();
        $types = $this->getAvailableTypes();

        return view('admin.settings.edit', compact('setting', 'groups', 'types'));
    }

    /**
     * 更新设置
     */
    public function update(Request $request, Setting $setting)
    {
        $validator = Validator::make($request->all(), [
            'setting_key' => 'required|string|max:255|unique:settings,setting_key,' . $setting->id,
            'setting_value' => 'nullable',
            'setting_group' => 'required|string|max:100',
            'setting_type' => 'required|in:string,integer,boolean,json,text',
            'description' => 'nullable|string|max:500',
            'is_autoload' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // 根据类型处理值
        $value = $this->processSettingValue(
            $request->input('setting_value'),
            $request->input('setting_type')
        );

        $oldGroup = $setting->setting_group;

        $setting->update([
            'setting_key' => $request->input('setting_key'),
            'setting_value' => $value,
            'setting_group' => $request->input('setting_group'),
            'setting_type' => $request->input('setting_type'),
            'description' => $request->input('description'),
            'is_autoload' => $request->boolean('is_autoload'),
        ]);

        do_action('admin.setting.updated', $setting, $oldGroup);

        return redirect()->route('admin.settings.group', $setting->setting_group)
            ->with('success', '设置已更新');
    }

    /**
     * 删除设置
     */
    public function destroy(Setting $setting)
    {
        $group = $setting->setting_group;
        
        do_action('admin.setting.before_delete', $setting);
        
        $setting->delete();
        
        do_action('admin.setting.deleted', $setting);

        return redirect()->route('admin.settings.group', $group)
            ->with('success', '设置已删除');
    }

    /**
     * 批量更新分组设置
     */
    public function updateGroup(Request $request, $group)
    {
        $settings = $request->input('settings', []);
        
        if (empty($settings)) {
            return back()->with('error', '没有设置需要更新');
        }

        $updatedCount = 0;
        foreach ($settings as $key => $value) {
            $setting = Setting::byKey($key)->first();
            if ($setting && $setting->setting_group === $group) {
                $processedValue = $this->processSettingValue($value, $setting->setting_type);
                $setting->update(['setting_value' => $processedValue]);
                $updatedCount++;
            }
        }

        do_action('admin.settings.group_updated', $group, $settings);

        return back()->with('success', "已成功更新 {$updatedCount} 个设置");
    }

    /**
     * 批量操作
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        
        switch ($action) {
            case 'update_all':
                return $this->updateAllSettings($request);
                
            case 'delete':
                $ids = $request->input('ids', []);
                if (empty($ids)) {
                    return response()->json(['success' => false, 'message' => '请选择要删除的设置']);
                }
                
                $settings = Setting::whereIn('id', $ids)->get();
                do_action('admin.settings.bulk_delete', $settings);
                Setting::whereIn('id', $ids)->delete();
                return response()->json(['success' => true, 'message' => "已删除 {$settings->count()} 个设置"]);
                
            case 'enable_autoload':
                $ids = $request->input('ids', []);
                if (empty($ids)) {
                    return response()->json(['success' => false, 'message' => '请选择要操作的设置']);
                }
                
                Setting::whereIn('id', $ids)->update(['is_autoload' => true]);
                return response()->json(['success' => true, 'message' => '已启用自动加载']);
                
            case 'disable_autoload':
                $ids = $request->input('ids', []);
                if (empty($ids)) {
                    return response()->json(['success' => false, 'message' => '请选择要操作的设置']);
                }
                
                Setting::whereIn('id', $ids)->update(['is_autoload' => false]);
                return response()->json(['success' => true, 'message' => '已禁用自动加载']);
                
            default:
                return response()->json(['success' => false, 'message' => '无效的操作']);
        }
    }

    /**
     * 更新所有设置
     */
    protected function updateAllSettings(Request $request)
    {
        $settings = $request->input('settings', []);
        
        if (empty($settings)) {
            return response()->json(['success' => false, 'message' => '没有设置需要更新']);
        }

        $updatedCount = 0;
        foreach ($settings as $key => $value) {
            $setting = Setting::byKey($key)->first();
            if ($setting) {
                $processedValue = $this->processSettingValue($value, $setting->setting_type);
                $setting->update(['setting_value' => $processedValue]);
                $updatedCount++;
            }
        }

        do_action('admin.settings.bulk_updated', $settings);

        return response()->json([
            'success' => true, 
            'message' => "已成功更新 {$updatedCount} 个设置"
        ]);
    }

    /**
     * 快速更新设置值（Ajax接口）
     */
    public function quickUpdate(Request $request)
    {
        $key = $request->input('key');
        $value = $request->input('value');

        $setting = Setting::byKey($key)->first();
        
        if (!$setting) {
            return response()->json(['success' => false, 'message' => '设置不存在']);
        }

        $processedValue = $this->processSettingValue($value, $setting->setting_type);
        $setting->update(['setting_value' => $processedValue]);

        do_action('admin.setting.quick_updated', $setting);

        return response()->json(['success' => true, 'message' => '设置已更新']);
    }

    /**
     * 导出设置为JSON文件
     */
    public function export(Request $request)
    {
        $groupFilter = $request->input('group');
        
        $query = Setting::orderBy('setting_group')->orderBy('setting_key');
        
        if ($groupFilter) {
            $query->where('setting_group', $groupFilter);
        }
        
        $settings = $query->get();
        
        $exportData = [
            'export_info' => [
                'date' => now()->toISOString(),
                'site_url' => config('app.url'),
                'app_name' => config('app.name'),
                'version' => '1.0.0',
                'total_settings' => $settings->count(),
            ],
            'settings' => $settings->mapWithKeys(function ($setting) {
                return [$setting->setting_key => [
                    'value' => $setting->setting_value,
                    'group' => $setting->setting_group,
                    'type' => $setting->setting_type,
                    'description' => $setting->description,
                    'is_autoload' => $setting->is_autoload,
                ]];
            })->toArray(),
        ];
        
        do_action('admin.settings.exported', $exportData);
        
        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="gei5cms-settings-' . now()->format('Y-m-d') . '.json"');
    }

    /**
     * 导入设置从JSON文件
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings_file' => 'required|file|mimes:json|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => '请上传有效的JSON文件']);
        }

        try {
            $file = $request->file('settings_file');
            $content = file_get_contents($file->getPathname());
            $importData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['success' => false, 'message' => 'JSON文件格式错误']);
            }

            if (!isset($importData['settings']) || !is_array($importData['settings'])) {
                return response()->json(['success' => false, 'message' => '设置文件格式不正确']);
            }

            $updatedCount = 0;
            $createdCount = 0;
            $skippedCount = 0;

            foreach ($importData['settings'] as $key => $settingData) {
                try {
                    $existingSetting = Setting::byKey($key)->first();
                    
                    if ($existingSetting) {
                        // 更新现有设置
                        $processedValue = $this->processSettingValue(
                            $settingData['value'] ?? $settingData,
                            $settingData['type'] ?? $existingSetting->setting_type
                        );
                        
                        $existingSetting->update([
                            'setting_value' => $processedValue,
                            'description' => $settingData['description'] ?? $existingSetting->description,
                            'is_autoload' => $settingData['is_autoload'] ?? $existingSetting->is_autoload,
                        ]);
                        $updatedCount++;
                    } else {
                        // 创建新设置
                        if (is_array($settingData) && isset($settingData['value'])) {
                            $processedValue = $this->processSettingValue(
                                $settingData['value'],
                                $settingData['type'] ?? 'string'
                            );
                            
                            Setting::create([
                                'setting_key' => $key,
                                'setting_value' => $processedValue,
                                'setting_group' => $settingData['group'] ?? 'general',
                                'setting_type' => $settingData['type'] ?? 'string',
                                'description' => $settingData['description'] ?? '',
                                'is_autoload' => $settingData['is_autoload'] ?? true,
                            ]);
                            $createdCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    $skippedCount++;
                    continue;
                }
            }

            do_action('admin.settings.imported', $importData, [
                'updated' => $updatedCount,
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ]);

            $message = "导入完成：更新 {$updatedCount} 项，新增 {$createdCount} 项";
            if ($skippedCount > 0) {
                $message .= "，跳过 {$skippedCount} 项";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => [
                    'updated' => $updatedCount,
                    'created' => $createdCount,
                    'skipped' => $skippedCount,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '导入失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 初始化默认设置
     */
    protected function initializeDefaultSettings()
    {
        $defaultSettings = [
            // 网站基础信息
            'site_name' => [
                'value' => 'Gei5CMS 网站',
                'group' => 'general',
                'type' => 'string',
                'description' => '网站名称，显示在浏览器标题栏和页面头部',
            ],
            'site_description' => [
                'value' => '基于Gei5CMS的多形态Web应用',
                'group' => 'general',
                'type' => 'text',
                'description' => '网站描述，用于SEO和页面介绍',
            ],
            'site_logo' => [
                'value' => '/images/logo.png',
                'group' => 'general',
                'type' => 'string',
                'description' => '网站Logo图片路径',
            ],
            'admin_email' => [
                'value' => 'admin@example.com',
                'group' => 'general',
                'type' => 'string',
                'description' => '管理员邮箱地址，用于系统通知',
            ],
            'timezone' => [
                'value' => 'Asia/Shanghai',
                'group' => 'general',
                'type' => 'string',
                'description' => '网站时区设置',
            ],
            'language' => [
                'value' => 'zh-CN',
                'group' => 'general',
                'type' => 'string',
                'description' => '默认语言',
            ],
            'date_format' => [
                'value' => 'Y-m-d',
                'group' => 'general',
                'type' => 'string',
                'description' => '日期显示格式',
            ],
            'time_format' => [
                'value' => 'H:i:s',
                'group' => 'general',
                'type' => 'string',
                'description' => '时间显示格式',
            ],

            // 性能与缓存
            'cache_enabled' => [
                'value' => true,
                'group' => 'performance',
                'type' => 'boolean',
                'description' => '启用系统缓存功能',
            ],
            'cache_ttl' => [
                'value' => 3600,
                'group' => 'performance',
                'type' => 'integer',
                'description' => '缓存过期时间（秒）',
            ],
            'page_cache_enabled' => [
                'value' => false,
                'group' => 'performance',
                'type' => 'boolean',
                'description' => '启用页面静态缓存',
            ],
            'compress_css' => [
                'value' => true,
                'group' => 'performance',
                'type' => 'boolean',
                'description' => '压缩CSS文件',
            ],
            'compress_js' => [
                'value' => true,
                'group' => 'performance',
                'type' => 'boolean',
                'description' => '压缩JavaScript文件',
            ],
            'max_upload_size' => [
                'value' => 10485760, // 10MB
                'group' => 'performance',
                'type' => 'integer',
                'description' => '最大上传文件大小（字节）',
            ],

            // 安全设置
            'max_login_attempts' => [
                'value' => 5,
                'group' => 'security',
                'type' => 'integer',
                'description' => '最大登录尝试次数',
            ],
            'session_timeout' => [
                'value' => 7200,
                'group' => 'security',
                'type' => 'integer',
                'description' => '会话超时时间（秒）',
            ],
            'enable_2fa' => [
                'value' => false,
                'group' => 'security',
                'type' => 'boolean',
                'description' => '启用双因素认证',
            ],
            'login_captcha' => [
                'value' => false,
                'group' => 'security',
                'type' => 'boolean',
                'description' => '登录时显示验证码',
            ],
            'password_min_length' => [
                'value' => 8,
                'group' => 'security',
                'type' => 'integer',
                'description' => '用户密码最小长度',
            ],
            'block_suspicious_ips' => [
                'value' => true,
                'group' => 'security',
                'type' => 'boolean',
                'description' => '自动封禁可疑IP地址',
            ],

            // 外观设置
            'theme_name' => [
                'value' => 'default',
                'group' => 'appearance',
                'type' => 'string',
                'description' => '当前使用的主题',
            ],
            'posts_per_page' => [
                'value' => 10,
                'group' => 'appearance',
                'type' => 'integer',
                'description' => '每页显示文章数量',
            ],
            'show_author' => [
                'value' => true,
                'group' => 'appearance',
                'type' => 'boolean',
                'description' => '显示文章作者信息',
            ],
            'show_date' => [
                'value' => true,
                'group' => 'appearance',
                'type' => 'boolean',
                'description' => '显示文章发布日期',
            ],
            'enable_comments' => [
                'value' => true,
                'group' => 'appearance',
                'type' => 'boolean',
                'description' => '启用评论功能',
            ],

            // 邮件设置
            'mail_driver' => [
                'value' => 'smtp',
                'group' => 'email',
                'type' => 'string',
                'description' => '邮件发送方式',
            ],
            'mail_host' => [
                'value' => 'smtp.gmail.com',
                'group' => 'email',
                'type' => 'string',
                'description' => 'SMTP服务器地址',
            ],
            'mail_port' => [
                'value' => 587,
                'group' => 'email',
                'type' => 'integer',
                'description' => 'SMTP服务器端口',
            ],
            'mail_username' => [
                'value' => '',
                'group' => 'email',
                'type' => 'string',
                'description' => 'SMTP用户名',
            ],
            'mail_from_name' => [
                'value' => 'Gei5CMS',
                'group' => 'email',
                'type' => 'string',
                'description' => '发件人姓名',
            ],

            // SEO设置
            'meta_keywords' => [
                'value' => 'Gei5CMS, 内容管理系统, CMS',
                'group' => 'seo',
                'type' => 'text',
                'description' => '网站默认关键词',
            ],
            'meta_description' => [
                'value' => 'Gei5CMS是一个强大的多形态Web应用引擎',
                'group' => 'seo',
                'type' => 'text',
                'description' => '网站默认描述',
            ],
            'enable_seo_urls' => [
                'value' => true,
                'group' => 'seo',
                'type' => 'boolean',
                'description' => '启用SEO友好的URL结构',
            ],
            'sitemap_enabled' => [
                'value' => true,
                'group' => 'seo',
                'type' => 'boolean',
                'description' => '自动生成网站地图',
            ],

            // 系统维护
            'maintenance_mode' => [
                'value' => false,
                'group' => 'advanced',
                'type' => 'boolean',
                'description' => '维护模式（暂时关闭网站）',
            ],
            'debug_mode' => [
                'value' => false,
                'group' => 'advanced',
                'type' => 'boolean',
                'description' => '调试模式（显示详细错误信息）',
            ],
            'log_level' => [
                'value' => 'info',
                'group' => 'advanced',
                'type' => 'string',
                'description' => '日志记录级别',
            ],
            'backup_frequency' => [
                'value' => 'daily',
                'group' => 'advanced',
                'type' => 'string',
                'description' => '自动备份频率',
            ],
        ];

        foreach ($defaultSettings as $key => $config) {
            Setting::create([
                'setting_key' => $key,
                'setting_value' => $config['type'] === 'json' 
                    ? json_encode($config['value']) 
                    : (string) $config['value'],
                'setting_group' => $config['group'],
                'setting_type' => $config['type'],
                'description' => $config['description'],
                'is_autoload' => true,
            ]);
        }
    }

    /**
     * 处理设置值
     */
    protected function processSettingValue($value, $type)
    {
        return match($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * 获取分组标签
     */
    protected function getGroupLabel($group)
    {
        return match($group) {
            'general' => '基本设置',
            'performance' => '性能设置',
            'security' => '安全设置',
            'appearance' => '外观设置',
            'email' => '邮件设置',
            'social' => '社交设置',
            'seo' => 'SEO设置',
            'advanced' => '高级设置',
            default => ucfirst($group) . '设置',
        };
    }

    /**
     * 获取分组描述
     */
    protected function getGroupDescription($group)
    {
        return match($group) {
            'general' => '网站基本信息和通用配置',
            'performance' => '缓存、优化和性能相关设置',
            'security' => '登录、权限和安全防护设置',
            'appearance' => '主题、界面和显示设置',
            'email' => '邮件发送和通知设置',
            'social' => '社交媒体和第三方集成',
            'seo' => '搜索引擎优化相关设置',
            'advanced' => '高级功能和开发者设置',
            default => $group . '相关配置选项',
        };
    }

    /**
     * 获取分组图标
     */
    protected function getGroupIcon($group)
    {
        return match($group) {
            'general' => 'bi bi-settings',
            'performance' => 'bi bi-activity',
            'security' => 'bi bi-shield',
            'appearance' => 'bi bi-palette',
            'email' => 'bi bi-envelope',
            'social' => 'bi bi-share-fill',
            'seo' => 'bi bi-search',
            'advanced' => 'bi bi-code-slash',
            default => 'bi bi-settings',
        };
    }

    /**
     * 获取可用分组
     */
    protected function getAvailableGroups()
    {
        return [
            'general' => '基本设置',
            'performance' => '性能设置',
            'security' => '安全设置',
            'appearance' => '外观设置',
            'email' => '邮件设置',
            'social' => '社交设置',
            'seo' => 'SEO设置',
            'advanced' => '高级设置',
        ];
    }

    /**
     * 获取可用类型
     */
    protected function getAvailableTypes()
    {
        return [
            'string' => '字符串',
            'integer' => '整数',
            'boolean' => '布尔值',
            'text' => '长文本',
            'json' => 'JSON数据',
        ];
    }
}