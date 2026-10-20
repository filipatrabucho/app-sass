<?php

namespace App\Core\Settings;

use App\Core\Settings\Models\Setting;

/**
 * Definições do tenant (tabela settings). Os módulos usam chaves com prefixo (ex.: vacations.hr_approval).
 */
class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::find($key);

        return $setting === null ? $default : ($setting->value['v'] ?? $default);
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => ['v' => $value]]);
    }
}
