<?php

namespace Domain\Event\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentEventPlace extends Model
{
    protected $fillable = [
        'user_id',
        'place_name',
        'country_code',
        'city',
        'postal_code',
        'address_line',
        'latitude',
        'longitude',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
