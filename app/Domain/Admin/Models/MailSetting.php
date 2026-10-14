<?php

namespace Domain\Admin\Models;

use Domain\Admin\Actions\ApplyMailSettingsAction;
use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => ApplyMailSettingsAction::flush());
        static::deleted(fn () => ApplyMailSettingsAction::flush());
    }

    protected $fillable = [
        'enabled',
        'host',
        'port',
        'scheme',
        'username',
        'password',
        'from_address',
        'from_name',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'port' => 'integer',
        'password' => 'encrypted',
    ];
}
