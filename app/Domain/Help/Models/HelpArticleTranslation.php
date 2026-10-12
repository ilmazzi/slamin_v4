<?php

namespace Domain\Help\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticleTranslation extends Model
{
    protected $fillable = [
        'help_article_id',
        'locale',
        'title',
        'body',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'help_article_id');
    }
}
