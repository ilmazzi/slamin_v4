<?php

namespace Domain\Chat\Models;

use Domain\Chat\Enums\ChatMessageType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chat_thread_id',
        'reply_to_message_id',
        'user_id',
        'message_type',
        'content',
        'edited_at',
    ];

    protected $casts = [
        'message_type' => ChatMessageType::class,
        'edited_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_message_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatMessageReaction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ChatMessageAttachment::class);
    }
}
