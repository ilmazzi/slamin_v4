<?php

namespace Domain\Group\Models;

use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupAffiliation extends Model
{
    protected $fillable = [
        'parent_group_id',
        'child_group_id',
        'status',
        'initiated_by_group_id',
        'handled_by_user_id',
        'handled_at',
    ];

    protected $casts = [
        'status' => GroupAffiliationStatus::class,
        'handled_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_group_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'child_group_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'initiated_by_group_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function involves(Group $group): bool
    {
        return (int) $this->parent_group_id === (int) $group->id
            || (int) $this->child_group_id === (int) $group->id;
    }

    public function counterpart(Group $group): ?Group
    {
        if ((int) $this->parent_group_id === (int) $group->id) {
            return $this->child;
        }

        if ((int) $this->child_group_id === (int) $group->id) {
            return $this->parent;
        }

        return null;
    }
}
