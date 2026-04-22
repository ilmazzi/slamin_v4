<?php

namespace Domain\Chat\Models;

use Domain\Chat\Enums\ChatThreadType;
use Domain\Group\Models\Group;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatThread extends Model
{
    protected $fillable = [
        'type',
        'direct_key',
        'group_id',
        'title',
        'created_by_user_id',
        'last_message_at',
    ];

    protected $casts = [
        'type' => ChatThreadType::class,
        'last_message_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function memberRecords(): HasMany
    {
        return $this->hasMany(ChatThreadMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_thread_members')
            ->withPivot(['joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function isDirect(): bool
    {
        return $this->type === ChatThreadType::Direct;
    }

    public function isGroup(): bool
    {
        return $this->type === ChatThreadType::Group;
    }

    public function isAttachedGroup(): bool
    {
        return $this->isGroup() && $this->group_id !== null;
    }
}
