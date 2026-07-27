<?php

namespace App\Core\Hooks;

final class HookManager
{
    /** @var array<string, array<int, array{callback: callable, priority: int}>> */
    private array $actions = [];

    /** @var array<string, array<int, array{callback: callable, priority: int}>> */
    private array $filters = [];

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }

    public function action(string $hook, mixed ...$args): void
    {
        foreach ($this->sorted($this->actions[$hook] ?? []) as $listener) {
            ($listener['callback'])(...$args);
        }
    }

    public function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->sorted($this->filters[$hook] ?? []) as $listener) {
            $value = ($listener['callback'])($value, ...$args);
        }

        return $value;
    }

    /**
     * @param  array<int, array{callback: callable, priority: int}>  $listeners
     * @return array<int, array{callback: callable, priority: int}>
     */
    private function sorted(array $listeners): array
    {
        usort($listeners, fn (array $a, array $b) => $a['priority'] <=> $b['priority']);

        return $listeners;
    }
}
