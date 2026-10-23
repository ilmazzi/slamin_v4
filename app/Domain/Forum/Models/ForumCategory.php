<?php

namespace Domain\Forum\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
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
        return $this->hasMany(ForumCategoryTranslation::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
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

    public function translatedDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');

        $translation = $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', $fallbackLocale)
            ?? $this->translations->first();

        return $translation?->description;
    }
}
