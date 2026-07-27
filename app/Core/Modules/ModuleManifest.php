<?php

namespace App\Core\Modules;

final class ModuleManifest
{
    /**
     * @param  array<string, string>  $permissions
     * @param  array<string, mixed>|callable|null  $menu
     */
    public function __construct(
        public readonly string $name,
        public array $permissions = [],
        public mixed $menu = null,
        public ?string $migrations = null,
        public ?string $viewsPath = null,
        public ?string $viewsNamespace = null,
        public bool $enabled = true,
    ) {}

    /**
     * @param  array<string, string>  $permissions
     */
    public function permissions(array $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @param  array<string, mixed>|callable  $menu
     */
    public function menu(callable|array $menu): self
    {
        $this->menu = $menu;

        return $this;
    }

    public function migrations(string $path): self
    {
        $this->migrations = $path;

        return $this;
    }

    public function views(string $path, string $namespace): self
    {
        $this->viewsPath = $path;
        $this->viewsNamespace = $namespace;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveMenu(): ?array
    {
        if ($this->menu === null) {
            return null;
        }

        return is_callable($this->menu) ? ($this->menu)() : $this->menu;
    }
}
