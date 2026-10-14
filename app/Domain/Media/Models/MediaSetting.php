<?php

namespace Domain\Media\Models;

use Domain\Media\Actions\ResolveMediaSettingsAction;
use Illuminate\Database\Eloquent\Model;

class MediaSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    protected static function booted(): void
    {
        $flush = fn () => app(ResolveMediaSettingsAction::class)->flush();

        static::saved($flush);
        static::deleted($flush);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting ? $setting->resolvedValue($default) : $default;
    }

    public function resolvedValue(mixed $default = null): mixed
    {
        $value = $this->value;

        if (is_array($value) && array_key_exists('value', $value) && count($value) === 1) {
            return $value['value'];
        }

        return $value ?? $default;
    }

    public static function setValue(string $key, mixed $value): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['value' => $value]],
        );
    }
}
