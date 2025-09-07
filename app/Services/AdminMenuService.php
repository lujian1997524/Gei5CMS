<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Theme;
use App\Models\Plugin;
use App\Facades\Hook;

/**
 * 后台管理菜单服务类
 * 
 * 负责处理主题和插件的动态菜单注册
 * 根据THEME_PLUGIN_MENU_SPECIFICATION.md规范实现
 */
class AdminMenuService
{
    protected static $menus = [];
    protected static $initialized = false;

    /**
     * 初始化菜单系统
     */
    public static function initialize()
    {
        if (static::$initialized) {
            return;
        }

        static::loadThemeMenus();
        static::loadPluginMenus();
        
        // 触发菜单初始化钩子，让插件和主题可以动态注册菜单
        Hook::doAction('admin.menu.init', static::class);
        
        static::$initialized = true;
    }

    /**
     * 注册菜单项
     * 
     * @param string $key 菜单唯一标识
     * @param array $menu 菜单配置数组
     */
    public static function register(string $key, array $menu)
    {
        // 验证菜单结构
        if (!static::validateMenu($menu)) {
            Log::warning("Invalid menu structure for key: {$key}");
            return;
        }

        static::$menus[$key] = $menu;
    }

    /**
     * 获取所有已注册的菜单
     * 
     * @return array
     */
    public static function getMenus(): array
    {
        static::initialize();
        
        // 按优先级和位置排序
        $sortedMenus = static::$menus;
        uasort($sortedMenus, function ($a, $b) {
            // 先按位置排序
            $positionOrder = ['top' => 1, 'middle' => 2, 'bottom' => 3];
            $aPos = $positionOrder[$a['position'] ?? 'middle'] ?? 2;
            $bPos = $positionOrder[$b['position'] ?? 'middle'] ?? 2;
            
            if ($aPos !== $bPos) {
                return $aPos <=> $bPos;
            }
            
            // 位置相同时按优先级排序 (数字越小优先级越高)
            $aPriority = $a['priority'] ?? 50;
            $bPriority = $b['priority'] ?? 50;
            
            return $aPriority <=> $bPriority;
        });

        // 通过钩子过滤器允许插件和主题修改菜单
        $filteredMenus = Hook::applyFilters('admin.menu.filter', $sortedMenus);
        
        return $filteredMenus;
    }

    /**
     * 获取指定位置的菜单
     * 
     * @param string $position top|middle|bottom
     * @return array
     */
    public static function getMenusByPosition(string $position): array
    {
        $allMenus = static::getMenus();
        
        return array_filter($allMenus, function ($menu) use ($position) {
            return ($menu['position'] ?? 'middle') === $position;
        });
    }

    /**
     * 加载活跃主题的菜单配置
     */
    protected static function loadThemeMenus()
    {
        $activeTheme = Theme::where('status', 'active')->first();
        
        if (!$activeTheme) {
            return;
        }

        $themePath = base_path("themes/{$activeTheme->slug}");
        $menuConfigPath = "{$themePath}/admin-menu.json";

        if (File::exists($menuConfigPath)) {
            try {
                $menuConfig = json_decode(File::get($menuConfigPath), true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($menuConfig['menus'])) {
                    foreach ($menuConfig['menus'] as $menu) {
                        $key = "theme_{$activeTheme->slug}_{$menu['key']}";
                        static::register($key, $menu);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to load theme menu config: {$e->getMessage()}");
            }
        }

        // 加载主题的服务提供者菜单 (如果存在)
        $serviceProviderPath = "{$themePath}/src/Providers/AdminMenuServiceProvider.php";
        if (File::exists($serviceProviderPath)) {
            static::loadThemeServiceProviderMenus($activeTheme->slug);
        }
    }

    /**
     * 加载所有活跃插件的菜单配置
     */
    protected static function loadPluginMenus()
    {
        $activePlugins = Plugin::where('status', 'active')->get();

        foreach ($activePlugins as $plugin) {
            $pluginPath = base_path("plugins/{$plugin->slug}");
            $menuConfigPath = "{$pluginPath}/admin-menu.json";

            if (File::exists($menuConfigPath)) {
                try {
                    $menuConfig = json_decode(File::get($menuConfigPath), true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($menuConfig['menus'])) {
                        foreach ($menuConfig['menus'] as $menu) {
                            $key = "plugin_{$plugin->slug}_{$menu['key']}";
                            static::register($key, $menu);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to load plugin menu config for {$plugin->slug}: {$e->getMessage()}");
                }
            }

            // 加载插件的服务提供者菜单 (如果存在)
            $serviceProviderPath = "{$pluginPath}/src/Providers/AdminMenuServiceProvider.php";
            if (File::exists($serviceProviderPath)) {
                static::loadPluginServiceProviderMenus($plugin->slug);
            }
        }
    }

    /**
     * 加载主题服务提供者的菜单注册
     */
    protected static function loadThemeServiceProviderMenus(string $themeSlug)
    {
        try {
            $className = "Themes\\{$themeSlug}\\Providers\\AdminMenuServiceProvider";
            
            if (class_exists($className)) {
                $provider = new $className(app());
                if (method_exists($provider, 'registerMenus')) {
                    $provider->registerMenus();
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to load theme service provider menus for {$themeSlug}: {$e->getMessage()}");
        }
    }

    /**
     * 加载插件服务提供者的菜单注册
     */
    protected static function loadPluginServiceProviderMenus(string $pluginSlug)
    {
        try {
            $className = "Plugins\\{$pluginSlug}\\Providers\\AdminMenuServiceProvider";
            
            if (class_exists($className)) {
                $provider = new $className(app());
                if (method_exists($provider, 'registerMenus')) {
                    $provider->registerMenus();
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to load plugin service provider menus for {$pluginSlug}: {$e->getMessage()}");
        }
    }

    /**
     * 验证菜单结构
     * 
     * @param array $menu
     * @return bool
     */
    protected static function validateMenu(array $menu): bool
    {
        $required = ['key', 'label'];
        
        foreach ($required as $field) {
            if (!isset($menu[$field]) || empty($menu[$field])) {
                return false;
            }
        }

        // 如果有子菜单，则不需要route，否则必须有route
        if (isset($menu['children']) && !empty($menu['children'])) {
            if (!is_array($menu['children'])) {
                return false;
            }
            
            foreach ($menu['children'] as $child) {
                if (!static::validateMenu($child)) {
                    return false;
                }
            }
        } else {
            // 没有子菜单时必须有route
            if (!isset($menu['route']) || empty($menu['route'])) {
                return false;
            }
        }

        // 验证position字段
        if (isset($menu['position']) && !in_array($menu['position'], ['top', 'middle', 'bottom'])) {
            return false;
        }

        return true;
    }

    /**
     * 渲染管理后台侧边栏菜单
     * 用于钩子系统调用
     */
    public static function renderSidebarMenus(): void
    {
        $menus = static::getMenus();
        
        // 触发菜单渲染前钩子
        Hook::doAction('admin.sidebar.menu.before', $menus);
        
        foreach ($menus as $menu) {
            static::renderMenuItem($menu);
        }
        
        // 触发菜单渲染后钩子
        Hook::doAction('admin.sidebar.menu.after', $menus);
    }

    /**
     * 渲染单个菜单项
     */
    protected static function renderMenuItem(array $menu): void
    {
        if (isset($menu['children']) && !empty($menu['children'])) {
            // 渲染有子菜单的项目
            echo '<div class="nav-section">';
            echo '<h6 class="nav-section-title">' . e($menu['label']) . '</h6>';
            
            foreach ($menu['children'] as $child) {
                static::renderChildMenuItem($child);
            }
            
            echo '</div>';
        } else {
            // 渲染单个菜单项
            static::renderChildMenuItem($menu);
        }
    }

    /**
     * 渲染子菜单项
     */
    protected static function renderChildMenuItem(array $menu): void
    {
        $url = route($menu['route'], $menu['params'] ?? []);
        $active = request()->routeIs($menu['active'] ?? $menu['route']) ? 'active' : '';
        $icon = $menu['icon'] ?? 'bi bi-circle';
        $badge = isset($menu['badge']) ? '<span class="nav-badge">' . e($menu['badge']) . '</span>' : '';
        
        echo '<a href="' . $url . '" class="nav-link ' . $active . '">';
        echo '<i class="' . $icon . ' nav-icon"></i>';
        echo e($menu['label']);
        echo $badge;
        echo '</a>';
    }

    /**
     * 清除所有已注册的菜单 (用于测试和重置)
     */
    public static function clear()
    {
        static::$menus = [];
        static::$initialized = false;
    }

    /**
     * 检查用户是否有访问菜单的权限
     * 
     * @param array $menu
     * @param mixed $user
     * @return bool
     */
    public static function canAccess(array $menu, $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        if (!$user) {
            return false;
        }

        // 如果菜单没有定义权限要求，默认允许访问
        if (!isset($menu['permissions'])) {
            return true;
        }

        $permissions = is_array($menu['permissions']) ? $menu['permissions'] : [$menu['permissions']];

        // 检查用户是否有任一权限
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取过滤后的菜单 (根据用户权限)
     * 
     * @param mixed $user
     * @return array
     */
    public static function getFilteredMenus($user = null): array
    {
        $allMenus = static::getMenus();
        $filteredMenus = [];

        foreach ($allMenus as $key => $menu) {
            if (static::canAccess($menu, $user)) {
                // 过滤子菜单
                if (isset($menu['children'])) {
                    $menu['children'] = array_filter($menu['children'], function ($child) use ($user) {
                        return static::canAccess($child, $user);
                    });
                }
                
                $filteredMenus[$key] = $menu;
            }
        }

        return $filteredMenus;
    }
}