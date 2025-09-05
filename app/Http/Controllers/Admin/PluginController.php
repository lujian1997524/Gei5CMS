<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PluginController extends Controller
{
    /**
     * Display a listing of plugins.
     */
    public function index()
    {
        // 获取数据库中的插件记录
        $pluginsFromDb = Plugin::orderBy('priority')->get()->keyBy('slug');
        
        // 扫描plugins目录中的实际插件
        $pluginsPath = base_path('plugins');
        $availablePlugins = [];
        
        if (File::exists($pluginsPath)) {
            $pluginDirs = File::directories($pluginsPath);
            
            foreach ($pluginDirs as $dir) {
                $slug = basename($dir);
                $manifestPath = $dir . '/plugin.json';
                
                if (File::exists($manifestPath)) {
                    try {
                        $manifest = json_decode(File::get($manifestPath), true);
                        
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $plugin = $pluginsFromDb->get($slug);
                            
                            $availablePlugins[$slug] = [
                                'slug' => $slug,
                                'name' => $manifest['name'] ?? $slug,
                                'description' => $manifest['description'] ?? '',
                                'version' => $manifest['version'] ?? '1.0.0',
                                'author' => $manifest['author'] ?? '',
                                'status' => $plugin->status ?? 'inactive',
                                'priority' => $plugin->priority ?? 50,
                                'installed' => $plugin ? true : false,
                                'manifest' => $manifest,
                                'path' => $dir
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to parse plugin manifest: {$slug}", ['error' => $e->getMessage()]);
                    }
                }
            }
        }
        
        return view('admin.plugins.index', compact('availablePlugins'));
    }

    /**
     * Show the form for creating a new plugin.
     */
    public function create()
    {
        // 插件创建页面 - 用于上传新插件
        return view('admin.plugins.create');
    }

    /**
     * Store a newly created plugin in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plugin_file' => 'required|file|mimes:zip|max:10240', // 最大10MB
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $file = $request->file('plugin_file');
            $pluginsPath = base_path('plugins');
            
            // 创建临时目录
            $tempPath = storage_path('app/temp/plugins');
            if (!File::exists($tempPath)) {
                File::makeDirectory($tempPath, 0755, true);
            }
            
            // 解压文件
            $zip = new \ZipArchive;
            $zipPath = $tempPath . '/' . $file->getClientOriginalName();
            $file->move($tempPath, $file->getClientOriginalName());
            
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($pluginsPath);
                $zip->close();
                
                // 清理临时文件
                File::delete($zipPath);
                
                return redirect()->route('admin.plugins.index')
                    ->with('success', '插件上传成功！请刷新页面查看新插件。');
            } else {
                return back()->with('error', '无法解压插件文件，请检查文件格式。');
            }
        } catch (\Exception $e) {
            Log::error('Plugin upload failed', ['error' => $e->getMessage()]);
            return back()->with('error', '插件上传失败：' . $e->getMessage());
        }
    }

    /**
     * Display the specified plugin.
     */
    public function show(string $slug)
    {
        $pluginPath = base_path("plugins/{$slug}");
        $manifestPath = "{$pluginPath}/plugin.json";
        
        if (!File::exists($manifestPath)) {
            return redirect()->route('admin.plugins.index')
                ->with('error', '插件不存在或配置文件缺失。');
        }

        try {
            $manifest = json_decode(File::get($manifestPath), true);
            $plugin = Plugin::where('slug', $slug)->first();
            
            $pluginData = [
                'slug' => $slug,
                'manifest' => $manifest,
                'plugin' => $plugin,
                'path' => $pluginPath,
                'installed' => $plugin ? true : false,
                'status' => $plugin->status ?? 'inactive'
            ];
            
            return view('admin.plugins.show', compact('pluginData'));
        } catch (\Exception $e) {
            Log::error("Failed to load plugin details: {$slug}", ['error' => $e->getMessage()]);
            return redirect()->route('admin.plugins.index')
                ->with('error', '无法加载插件详情：' . $e->getMessage());
        }
    }

    /**
     * Activate a plugin.
     */
    public function activate(string $slug)
    {
        try {
            $pluginPath = base_path("plugins/{$slug}");
            $manifestPath = "{$pluginPath}/plugin.json";
            
            if (!File::exists($manifestPath)) {
                return back()->with('error', '插件配置文件不存在。');
            }

            $manifest = json_decode(File::get($manifestPath), true);
            
            // 创建或更新插件记录
            $plugin = Plugin::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $manifest['name'] ?? $slug,
                    'version' => $manifest['version'] ?? '1.0.0',
                    'description' => $manifest['description'] ?? '',
                    'author' => is_array($manifest['author'] ?? '') 
                        ? ($manifest['author']['name'] ?? '') 
                        : ($manifest['author'] ?? ''),
                    'status' => 'active',
                    'priority' => $manifest['priority'] ?? 50
                ]
            );

            // 触发插件激活钩子
            do_action('gei5_plugin_activated', $slug, $plugin);
            
            return back()->with('success', "插件 {$plugin->name} 已激活。");
        } catch (\Exception $e) {
            Log::error("Failed to activate plugin: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '激活插件失败：' . $e->getMessage());
        }
    }

    /**
     * Deactivate a plugin.
     */
    public function deactivate(string $slug)
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();
            
            if (!$plugin) {
                return back()->with('error', '插件记录不存在。');
            }

            $plugin->update(['status' => 'inactive']);
            
            // 触发插件停用钩子
            do_action('gei5_plugin_deactivated', $slug, $plugin);
            
            return back()->with('success', "插件 {$plugin->name} 已停用。");
        } catch (\Exception $e) {
            Log::error("Failed to deactivate plugin: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '停用插件失败：' . $e->getMessage());
        }
    }

    /**
     * Remove the specified plugin from storage.
     */
    public function destroy(string $slug)
    {
        try {
            $plugin = Plugin::where('slug', $slug)->first();
            $pluginPath = base_path("plugins/{$slug}");
            
            // 先停用插件
            if ($plugin && $plugin->status === 'active') {
                $this->deactivate($slug);
            }
            
            // 触发插件删除前钩子
            do_action('gei5_plugin_before_delete', $slug, $plugin);
            
            // 删除文件夹
            if (File::exists($pluginPath)) {
                File::deleteDirectory($pluginPath);
            }
            
            // 删除数据库记录
            if ($plugin) {
                $plugin->delete();
            }
            
            // 触发插件删除后钩子
            do_action('gei5_plugin_deleted', $slug);
            
            return redirect()->route('admin.plugins.index')
                ->with('success', '插件已删除。');
        } catch (\Exception $e) {
            Log::error("Failed to delete plugin: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '删除插件失败：' . $e->getMessage());
        }
    }

    /**
     * Update plugin priority.
     */
    public function updatePriority(Request $request, string $slug)
    {
        $validator = Validator::make($request->all(), [
            'priority' => 'required|integer|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $plugin = Plugin::where('slug', $slug)->first();
            
            if (!$plugin) {
                return back()->with('error', '插件不存在。');
            }

            $plugin->update(['priority' => $request->priority]);
            
            return back()->with('success', '插件优先级已更新。');
        } catch (\Exception $e) {
            Log::error("Failed to update plugin priority: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '更新失败：' . $e->getMessage());
        }
    }
}