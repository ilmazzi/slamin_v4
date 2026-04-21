<?php

namespace Domain\Admin\Models;

use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Illuminate\Database\Eloquent\Model;

class ContentPublicationSetting extends Model
{
    protected $fillable = [
        'content_type',
        'publication_flow',
        'is_active',
    ];

    protected $casts = [
        'content_type' => ContentType::class,
        'publication_flow' => PublicationFlow::class,
        'is_active' => 'boolean',
    ];
}
