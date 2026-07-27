<?php

namespace App\Core\Modules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ModuleRegistry
{
    /** @var array<string, ModuleManifest> */
    private array $modules = [];

    public function register(string $name): ModuleManifest
    {
        $manifest = new ModuleManifest($name);
        $this->modules[$name] = $manifest;

        return $manifest;
    }

    public function get(string $name): ?ModuleManifest
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * @return Collection<string, ModuleManifest>
     */
    public function all(): Collection
    {
        return collect($this->modules);
    }

    public function isEnabled(string $name): bool
    {
        if (! isset($this->modules[$name])) {
            return false;
        }

        if (! Schema::hasTable('modules')) {
            return $this->modules[$name]->enabled;
        }

        $enabled = Cache::remember("modules.{$name}.enabled", 60, function () use ($name) {
            /** @var object{is_enabled: int|bool}|null $row */
            $row = DB::table('modules')->where('name', $name)->first();

            return $row ? (bool) $row->is_enabled : true;
        });

        return $enabled;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function menuItems(): array
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = $this->all()
            ->filter(fn (ModuleManifest $m, string $name) => $this->isEnabled($name))
            ->map(fn (ModuleManifest $m) => $m->resolveMenu())
            ->filter()
            ->sortBy(fn (array $item) => $item['order'] ?? 100)
            ->values()
            ->all();

        return $items;
    }

    /**
     * @return array<string, string>
     */
    public function allPermissions(): array
    {
        $permissions = [];

        foreach ($this->modules as $name => $manifest) {
            if (! $this->isEnabled($name)) {
                continue;
            }

            $permissions = array_merge($permissions, $manifest->permissions);
        }

        return $permissions;
    }

    public function forgetCache(string $name): void
    {
        Cache::forget("modules.{$name}.enabled");
    }
}
