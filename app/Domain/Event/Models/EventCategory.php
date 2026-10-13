<?php

namespace Domain\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    public const SYSTEM_SLUGS = ['festival', 'poetry-slam'];

    protected $fillable = [
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(EventCategoryTranslation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function isSystemCategory(): bool
    {
        return in_array($this->slug, self::SYSTEM_SLUGS, true);
    }

    public function translatedName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');

        $translation = $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', $fallbackLocale)
            ?? $this->translations->first();

        return $translation?->name;
    }
}
