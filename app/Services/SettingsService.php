<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    /**
     * @return array<string,string>
     */
    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::remember('settings.all', 60, function () {
            return Setting::query()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public function set(string $key, mixed $value, ?string $group = null): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'group' => $group]
        );

        Cache::forget('settings.all');
    }

    /**
     * @param array<string,mixed> $values
     */
    public function setMany(array $values, ?string $group = null): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $group);
        }
    }
}
