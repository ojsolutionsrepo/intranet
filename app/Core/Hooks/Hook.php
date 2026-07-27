<?php

namespace App\Core\Hooks;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void addAction(string $hook, callable $callback, int $priority = 10)
 * @method static void addFilter(string $hook, callable $callback, int $priority = 10)
 * @method static void action(string $hook, mixed ...$args)
 * @method static mixed filter(string $hook, mixed $value, mixed ...$args)
 *
 * @see HookManager
 */
class Hook extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
