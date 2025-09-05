<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool registerHook(string $tag, callable $callback, int $priority = 10, string $pluginSlug = null, string $hookType = 'action')
 * @method static bool unregisterHook(string $tag, callable $callback = null, string $pluginSlug = null)
 * @method static array executeHook(string $tag, ...$args)
 * @method static void executeHookAsync(string $tag, ...$args)
 * @method static mixed executeFilter(string $tag, $value, ...$args)
 * @method static array doAction(string $tag, ...$args)
 * @method static mixed applyFilters(string $tag, $value, ...$args)
 * @method static bool removeHook(string $tag, string $source = null)
 * @method static bool hasHook(string $tag)
 * @method static array getRegisteredHooks(string $tag = null)
 * @method static void loadHooksFromDatabase()
 * @method static array getHookStatistics()
 */
class Hook extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'hook.manager';
    }
}