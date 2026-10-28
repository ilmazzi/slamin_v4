<?php

namespace Domain\Event\Models;

use Domain\Event\Support\EventCategoryLocaleNames;
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

        $fromDb = $this->translations->firstWhere('locale', $locale)?->name;
        if (filled($fromDb)) {
            return $fromDb;
        }

        $fromLang = EventCategoryLocaleNames::nameFor((string) $this->slug, $locale);
        if (filled($fromLang)) {
            return $fromLang;
        }

        return $this->translations->firstWhere('locale', config('app.fallback_locale'))?->name
            ?? $this->translations->first()?->name;
    }
}
