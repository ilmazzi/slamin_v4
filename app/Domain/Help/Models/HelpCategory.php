<?php

namespace Domain\Help\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpCategory extends Model
{
    protected $fillable = [
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class, 'help_category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HelpCategoryTranslation::class, 'help_category_id');
    }

    public function translatedName(?string $locale = null): ?string
    {
        return $this->resolveTranslation($locale)?->name;
    }

    public function resolveTranslation(?string $locale = null): ?HelpCategoryTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations
            ->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();
    }
}
