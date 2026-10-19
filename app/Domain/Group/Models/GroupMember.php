<?php

namespace Domain\Group\Models;

use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'requested_role',
        'status',
        'joined_at',
        'handled_by_user_id',
        'handled_at',
    ];

    protected $casts = [
        'role' => GroupMemberRole::class,
        'requested_role' => GroupMemberRole::class,
        'status' => GroupMemberStatus::class,
        'joined_at' => 'datetime',
        'handled_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }
}
