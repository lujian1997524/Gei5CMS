<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ThemeController extends Controller
{
    /**
     * Display a listing of themes.
     */
    public function index()
    {
        // 获取数据库中的主题记录
        $themesFromDb = Theme::orderBy('created_at', 'desc')->get()->keyBy('slug');
        
        // 扫描themes目录中的实际主题
        $themesPath = base_path('themes');
        $availableThemes = [];
        
        if (File::exists($themesPath)) {
            $themeDirs = File::directories($themesPath);
            
            foreach ($themeDirs as $dir) {
                $slug = basename($dir);
                $manifestPath = $dir . '/theme.json';
                
                if (File::exists($manifestPath)) {
                    try {
                        $manifest = json_decode(File::get($manifestPath), true);
                        
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $theme = $themesFromDb->get($slug);
                            
                            // 查找预览图
                            $screenshots = [];
                            foreach (['screenshot.jpg', 'screenshot.png', 'preview.jpg', 'preview.png'] as $filename) {
                                if (File::exists($dir . '/' . $filename)) {
                                    $screenshots[] = '/themes/' . $slug . '/' . $filename;
                                }
                            }
                            
                            $availableThemes[$slug] = [
                                'slug' => $slug,
                                'name' => $manifest['name'] ?? $slug,
                                'description' => $manifest['description'] ?? '',
                                'version' => $manifest['version'] ?? '1.0.0',
                                'author' => $manifest['author'] ?? '',
                                'status' => $theme->status ?? 'inactive',
                                'installed' => $theme ? true : false,
                                'manifest' => $manifest,
                                'path' => $dir,
                                'screenshots' => $screenshots,
                                'supports' => $manifest['supports'] ?? [],
                                'tags' => $manifest['tags'] ?? []
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to parse theme manifest: {$slug}", ['error' => $e->getMessage()]);
                    }
                }
            }
        }
        
        return view('admin.themes.index', compact('availableThemes'));
    }

    /**
     * Show the form for creating a new theme.
     */
    public function create()
    {
        // 主题创建页面 - 用于上传新主题
        return view('admin.themes.create');
    }

    /**
     * Store a newly created theme in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_file' => 'required|file|mimes:zip|max:20480', // 最大20MB
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $file = $request->file('theme_file');
            $themesPath = base_path('themes');
            
            // 创建临时目录
            $tempPath = storage_path('app/temp/themes');
            if (!File::exists($tempPath)) {
                File::makeDirectory($tempPath, 0755, true);
            }
            
            // 解压文件
            $zip = new \ZipArchive;
            $zipPath = $tempPath . '/' . $file->getClientOriginalName();
            $file->move($tempPath, $file->getClientOriginalName());
            
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($themesPath);
                $zip->close();
                
                // 清理临时文件
                File::delete($zipPath);
                
                return redirect()->route('admin.themes.index')
                    ->with('success', '主题上传成功！请刷新页面查看新主题。');
            } else {
                return back()->with('error', '无法解压主题文件，请检查文件格式。');
            }
        } catch (\Exception $e) {
            Log::error('Theme upload failed', ['error' => $e->getMessage()]);
            return back()->with('error', '主题上传失败：' . $e->getMessage());
        }
    }

    /**
     * Display the specified theme.
     */
    public function show(string $slug)
    {
        $themePath = base_path("themes/{$slug}");
        $manifestPath = "{$themePath}/theme.json";
        
        if (!File::exists($manifestPath)) {
            return redirect()->route('admin.themes.index')
                ->with('error', '主题不存在或配置文件缺失。');
        }

        try {
            $manifest = json_decode(File::get($manifestPath), true);
            $theme = Theme::where('slug', $slug)->first();
            
            // 获取主题文件信息
            $themeFiles = [];
            $viewsPath = "{$themePath}/views";
            if (File::exists($viewsPath)) {
                $themeFiles['views'] = count(File::allFiles($viewsPath));
            }
            
            $assetsPath = "{$themePath}/assets";
            if (File::exists($assetsPath)) {
                $themeFiles['assets'] = count(File::allFiles($assetsPath));
            }
            
            $themeData = [
                'slug' => $slug,
                'manifest' => $manifest,
                'theme' => $theme,
                'path' => $themePath,
                'installed' => $theme ? true : false,
                'status' => $theme->status ?? 'inactive',
                'files' => $themeFiles
            ];
            
            return view('admin.themes.show', compact('themeData'));
        } catch (\Exception $e) {
            Log::error("Failed to load theme details: {$slug}", ['error' => $e->getMessage()]);
            return redirect()->route('admin.themes.index')
                ->with('error', '无法加载主题详情：' . $e->getMessage());
        }
    }

    /**
     * Activate a theme.
     */
    public function activate(string $slug)
    {
        try {
            $themePath = base_path("themes/{$slug}");
            $manifestPath = "{$themePath}/theme.json";
            
            if (!File::exists($manifestPath)) {
                return back()->with('error', '主题配置文件不存在。');
            }

            $manifest = json_decode(File::get($manifestPath), true);
            
            // 先停用所有其他主题（一次只能激活一个主题）
            Theme::where('status', 'active')->update(['status' => 'inactive']);
            
            // 创建或更新主题记录
            $theme = Theme::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $manifest['name'] ?? $slug,
                    'version' => $manifest['version'] ?? '1.0.0',
                    'description' => $manifest['description'] ?? '',
                    'author' => is_array($manifest['author'] ?? '') 
                        ? ($manifest['author']['name'] ?? '') 
                        : ($manifest['author'] ?? ''),
                    'status' => 'active'
                ]
            );

            // 触发主题激活钩子
            do_action('gei5_theme_activated', $slug, $theme);
            
            return back()->with('success', "主题 {$theme->name} 已激活。");
        } catch (\Exception $e) {
            Log::error("Failed to activate theme: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '激活主题失败：' . $e->getMessage());
        }
    }

    /**
     * Deactivate a theme.
     */
    public function deactivate(string $slug)
    {
        try {
            $theme = Theme::where('slug', $slug)->first();
            
            if (!$theme) {
                return back()->with('error', '主题记录不存在。');
            }

            $theme->update(['status' => 'inactive']);
            
            // 触发主题停用钩子
            do_action('gei5_theme_deactivated', $slug, $theme);
            
            return back()->with('success', "主题 {$theme->name} 已停用。");
        } catch (\Exception $e) {
            Log::error("Failed to deactivate theme: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '停用主题失败：' . $e->getMessage());
        }
    }

    /**
     * Remove the specified theme from storage.
     */
    public function destroy(string $slug)
    {
        try {
            $theme = Theme::where('slug', $slug)->first();
            $themePath = base_path("themes/{$slug}");
            
            // 检查是否是当前激活的主题
            if ($theme && $theme->status === 'active') {
                return back()->with('error', '无法删除当前激活的主题，请先切换到其他主题。');
            }
            
            // 触发主题删除前钩子
            do_action('gei5_theme_before_delete', $slug, $theme);
            
            // 删除文件夹
            if (File::exists($themePath)) {
                File::deleteDirectory($themePath);
            }
            
            // 删除数据库记录
            if ($theme) {
                $theme->delete();
            }
            
            // 触发主题删除后钩子
            do_action('gei5_theme_deleted', $slug);
            
            return redirect()->route('admin.themes.index')
                ->with('success', '主题已删除。');
        } catch (\Exception $e) {
            Log::error("Failed to delete theme: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '删除主题失败：' . $e->getMessage());
        }
    }

    /**
     * Preview theme.
     */
    public function preview(string $slug)
    {
        $themePath = base_path("themes/{$slug}");
        $manifestPath = "{$themePath}/theme.json";
        
        if (!File::exists($manifestPath)) {
            return redirect()->route('admin.themes.index')
                ->with('error', '主题不存在。');
        }

        try {
            $manifest = json_decode(File::get($manifestPath), true);
            
            // 这里可以实现主题预览逻辑
            // 暂时重定向到主题详情页
            return redirect()->route('admin.themes.show', $slug);
        } catch (\Exception $e) {
            Log::error("Failed to preview theme: {$slug}", ['error' => $e->getMessage()]);
            return back()->with('error', '预览主题失败：' . $e->getMessage());
        }
    }

    /**
     * Customize theme settings.
     */
    public function customize(string $slug)
    {
        $theme = Theme::where('slug', $slug)->where('status', 'active')->first();
        
        if (!$theme) {
            return back()->with('error', '只能自定义当前激活的主题。');
        }

        // 这里可以实现主题自定义逻辑
        return view('admin.themes.customize', compact('theme'));
    }
}