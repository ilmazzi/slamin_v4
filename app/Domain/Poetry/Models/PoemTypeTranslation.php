<?php

namespace Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoemTypeTranslation extends Model
{
    protected $fillable = [
        'poem_type_id',
        'locale',
        'name',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(PoemType::class, 'poem_type_id');
    }
}
