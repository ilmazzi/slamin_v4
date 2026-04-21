<?php

namespace Domain\Article\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleCategoryTranslation extends Model
{
    protected $fillable = [
        'article_category_id',
        'locale',
        'name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }
}
