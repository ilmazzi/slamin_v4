<?php

namespace Domain\I18n\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationKey extends Model
{
    protected $table = 'i18n_translation_keys';

    protected $fillable = [
        'key',
        'group',
        'notes',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'translation_key_id');
    }
}
