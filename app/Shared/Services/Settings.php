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

        if (! array_key_exists($key, $all) || $all[$key] === null) {
            return $default;
        }

        return $all[$key];
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
        $this->applyToConfig();
    }

    /**
     * Push persisted settings into runtime config so consumers of config()
     * (privacy notice, mail footers, page titles) see admin changes immediately.
     */
    public function applyToConfig(): void
    {
        $siteName = $this->get('site_name');
        if (is_string($siteName) && $siteName !== '') {
            config(['app.name' => $siteName]);
        }

        $privacy = $this->get('privacy_contact');
        if (is_string($privacy) && $privacy !== '') {
            config(['gdpr.privacy_contact' => $privacy]);
        }
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
                ->mapWithKeys(fn ($row) => [$row->key => $this->decode($row->value)])
                ->all();
        });
    }

    private function decode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
