<?php

namespace Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoemCategory extends Model
{
    protected $fillable = [
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class, 'poem_category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PoemCategoryTranslation::class, 'poem_category_id');
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->translatedName());
    }

    public function translatedName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        $translation = $this->translations
            ->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();

        return $translation?->name;
    }
}
