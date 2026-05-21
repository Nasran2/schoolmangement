<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    /**
     * @var array<string,string>|null
     */
    private ?array $settings = null;

    private ?bool $settingsTableExists = null;

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
        if ($this->settings !== null) {
            return $this->settings;
        }

        if (! $this->settingsTableExists()) {
            return [];
        }

        try {
            $this->settings = Cache::remember('settings.all', 60, function () {
                return Setting::query()
                    ->pluck('value', 'key')
                    ->toArray();
            });
        } catch (\Throwable $e) {
            Log::warning('Unable to load application settings from the database.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->settings = [];
        }

        return $this->settings;
    }

    public function set(string $key, mixed $value, ?string $group = null): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'group' => $group]
        );

        Cache::forget('settings.all');
        $this->settings = null;
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

    private function settingsTableExists(): bool
    {
        if ($this->settingsTableExists !== null) {
            return $this->settingsTableExists;
        }

        try {
            $this->settingsTableExists = Schema::hasTable('settings');
        } catch (\Throwable $e) {
            Log::warning('Unable to check whether the settings table exists.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->settingsTableExists = false;
        }

        return $this->settingsTableExists;
    }
}
