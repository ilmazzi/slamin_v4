<?php

namespace Domain\Help\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpArticle extends Model
{
    protected $fillable = [
        'help_category_id',
        'slug',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HelpArticleTranslation::class, 'help_article_id');
    }

    public function translatedTitle(?string $locale = null): ?string
    {
        return $this->resolveTranslation($locale)?->title;
    }

    public function translatedBody(?string $locale = null): ?string
    {
        return $this->resolveTranslation($locale)?->body;
    }

    public function resolveTranslation(?string $locale = null): ?HelpArticleTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations
            ->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();
    }
}
