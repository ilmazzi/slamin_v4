<?php

namespace Domain\Newsletter\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NewsletterCampaign extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'subject',
        'content',
        'status',
        'recipient_count',
        'sent_at',
        'failure_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $campaign): void {
            $campaign->public_id ??= (string) Str::uuid();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
