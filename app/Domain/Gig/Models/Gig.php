<?php

namespace Domain\Gig\Models;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'giggable_type',
        'giggable_id',
        'type',
        'quantity',
        'filled_quantity',
        'language',
        'hospitality_required',
        'description',
        'proposed_price',
        'currency',
        'deadline',
        'special_requirements',
        'cachet_enabled',
        'travel_expenses_enabled',
        'travel_expenses_max',
        'accommodation_enabled',
        'accommodation_details',
        'status',
        'opened_at',
        'filled_at',
        'closed_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'type' => GigType::class,
        'status' => GigStatus::class,
        'hospitality_required' => 'boolean',
        'proposed_price' => 'decimal:2',
        'cachet_enabled' => 'boolean',
        'travel_expenses_enabled' => 'boolean',
        'travel_expenses_max' => 'decimal:2',
        'accommodation_enabled' => 'boolean',
        'deadline' => 'datetime',
        'opened_at' => 'datetime',
        'filled_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function giggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(GigApplication::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(GigAnnouncement::class);
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(GigNegotiation::class);
    }

    public function translationJobs(): HasMany
    {
        return $this->hasMany(TranslationJob::class);
    }

    public function scopeWithPublicParent(Builder $query): Builder
    {
        return $query->whereHasMorph('giggable', [Event::class, Poem::class], function (Builder $parent, string $type): void {
            $parent
                ->where('status', $type === Event::class ? EventStatus::Published->value : PoemStatus::Published->value)
                ->where('is_public', true);
        });
    }

    public function scopeManagedBy(Builder $query, int $userId): Builder
    {
        return $query->whereHasMorph('giggable', [Event::class, Poem::class], function (Builder $parent) use ($userId): void {
            $parent->where('user_id', $userId);
        });
    }

    public function ownerUserId(): ?int
    {
        $ownerId = $this->giggable?->user_id;

        return $ownerId === null ? null : (int) $ownerId;
    }

    public function isManagedBy(?int $userId): bool
    {
        return $userId !== null && $this->ownerUserId() === $userId;
    }

    public function hasPublicParent(): bool
    {
        $parent = $this->giggable;

        if ($parent instanceof Event) {
            return $parent->status === EventStatus::Published && $parent->is_public === true;
        }

        if ($parent instanceof Poem) {
            return $parent->status === PoemStatus::Published && $parent->is_public === true;
        }

        return false;
    }

    public function isListed(): bool
    {
        return ! in_array($this->status, [GigStatus::Draft, GigStatus::Cancelled], true)
            && $this->hasPublicParent();
    }

    public function isVisibleTo(?int $userId): bool
    {
        if ($this->isManagedBy($userId) || $this->isListed()) {
            return true;
        }

        if ($userId === null) {
            return false;
        }

        if (array_key_exists('my_application_id', $this->getAttributes())) {
            return $this->getAttribute('my_application_id') !== null;
        }

        return $this->applications()->where('user_id', $userId)->exists();
    }
}
