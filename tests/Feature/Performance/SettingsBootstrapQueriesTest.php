<?php

use Domain\Admin\Actions\ApplyMailSettingsAction;
use Domain\Admin\Models\MailSetting;
use Domain\Media\Actions\ResolveMediaSettingsAction;
use Domain\Media\Models\MediaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function settingsBootstrapQueryCount(callable $callback): int
{
    $count = 0;
    DB::listen(function () use (&$count): void {
        $count++;
    });

    $callback();

    return $count;
}

it('resolves media settings with a single query and reuses the result', function () {
    MediaSetting::setValue('photo_limit', 12);
    app()->forgetInstance(ResolveMediaSettingsAction::class);
    cache()->forget(ResolveMediaSettingsAction::CACHE_KEY);

    $queries = settingsBootstrapQueryCount(function (): void {
        $action = app(ResolveMediaSettingsAction::class);

        expect($action->execute()['photo_limit'])->toBe(12)
            ->and($action->execute()['video_limit'])->toBe(3);

        app(ResolveMediaSettingsAction::class)->execute();
    });

    expect($queries)->toBe(1);
});

it('invalidates cached media settings when a setting changes', function () {
    $action = app(ResolveMediaSettingsAction::class);
    expect($action->execute()['photo_limit'])->toBe(50);

    MediaSetting::setValue('photo_limit', 7);

    expect(app(ResolveMediaSettingsAction::class)->execute()['photo_limit'])->toBe(7);
});

it('applies cached smtp settings when the mailer is resolved and refreshes them on save', function () {
    MailSetting::query()->create([
        'enabled' => true,
        'host' => 'smtp.example.test',
        'port' => 587,
        'from_address' => 'noreply@example.test',
    ]);

    app(ApplyMailSettingsAction::class)->execute();
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.test');

    MailSetting::query()->firstOrFail()->update(['host' => 'smtp.changed.test']);

    $queries = settingsBootstrapQueryCount(fn () => app(ApplyMailSettingsAction::class)->execute());
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.changed.test');

    $cachedQueries = settingsBootstrapQueryCount(fn () => app(ApplyMailSettingsAction::class)->execute());
    expect($cachedQueries)->toBe(0)
        ->and($queries)->toBeGreaterThan(0);
});
