<?php

namespace Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoemCategoryTranslation extends Model
{
    protected $fillable = [
        'poem_category_id',
        'locale',
        'name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PoemCategory::class, 'poem_category_id');
    }
}
