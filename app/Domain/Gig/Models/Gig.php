<?php

namespace Domain\Gig\Models;

use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Domain\User\Models\User;
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
}
