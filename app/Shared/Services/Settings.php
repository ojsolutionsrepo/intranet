<?php

namespace App\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class Settings
{
    private const CACHE_KEY = 'app.settings';

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return data_get($all, $key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => json_encode($value),
                'group' => $group,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, 300, function () {
            return DB::table('settings')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->key => json_decode($row->value, true)])
                ->all();
        });
    }
}
