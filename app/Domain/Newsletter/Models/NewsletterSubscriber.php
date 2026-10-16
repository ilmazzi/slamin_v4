<?php

namespace Domain\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'locale',
        'status',
        'confirmation_token_hash',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subscriber): void {
            $subscriber->public_id ??= (string) Str::uuid();
        });
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed')->whereNotNull('confirmed_at');
    }
}
