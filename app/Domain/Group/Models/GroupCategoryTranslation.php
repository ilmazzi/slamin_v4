<?php

namespace Domain\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupCategoryTranslation extends Model
{
    protected $fillable = [
        'group_category_id',
        'locale',
        'name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(GroupCategory::class, 'group_category_id');
    }
}
