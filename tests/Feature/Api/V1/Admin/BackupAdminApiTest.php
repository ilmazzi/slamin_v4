<?php

use Domain\Backup\Actions\PerformSiteBackupAction;
use Domain\Backup\Support\BackupRunState;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use League\Flysystem\UnableToListContents;

uses(RefreshDatabase::class);

function backupAdminUser(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $role = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

it('lets an admin list backup status', function () {
    Sanctum::actingAs(backupAdminUser());

    $this->getJson('/api/v1/admin/backups')
        ->assertOk()
        ->assertJsonPath('data.disk', 'backups')
        ->assertJsonPath('data.configured', false)
        ->assertJsonPath('data.run.state', 'idle')
        ->assertJsonStructure([
            'data' => [
                'configured',
                'reachable',
                'healthy',
                'disk',
                'used_bytes',
                'newest_at',
                'error',
                'backups',
                'run' => [
                    'state',
                    'queued_at',
                    'finished_at',
                    'error',
                ],
            ],
        ]);
});

it('requests a backup when an admin asks to run one', function () {
    Sanctum::actingAs(backupAdminUser());

    $this->postJson('/api/v1/admin/backups')
        ->assertOk()
        ->assertJsonPath('data.queued', true);

    expect(BackupRunState::requested())->toBeTrue()
        ->and(BackupRunState::status()['state'])->toBe('queued');
});

it('forbids audience users from the backup panel', function () {
    Sanctum::actingAs(User::factory()->create([
        'email_verified_at' => now(),
    ]));

    $this->getJson('/api/v1/admin/backups')->assertForbidden();
    $this->postJson('/api/v1/admin/backups')->assertForbidden();
});

it('treats an empty r2 prefix as reachable', function () {
    config([
        'filesystems.disks.backups.key' => 'testing',
        'filesystems.disks.backups.secret' => 'testing',
        'filesystems.disks.backups.bucket' => 'slamin',
        'filesystems.disks.backups.endpoint' => 'https://example.r2.cloudflarestorage.com',
    ]);

    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('files')->andThrow(
        UnableToListContents::atLocation(
            'slamin',
            false,
            new RuntimeException('AWS HTTP error: NoSuchKey (client): The specified key does not exist.')
        )
    );
    $disk->shouldReceive('allFiles')->andReturn([]);

    $this->app['filesystem']->set('backups', $disk);

    Sanctum::actingAs(backupAdminUser());

    $this->getJson('/api/v1/admin/backups')
        ->assertOk()
        ->assertJsonPath('data.configured', true)
        ->assertJsonPath('data.reachable', true)
        ->assertJsonPath('data.backups', []);
});

it('runs a pending backup from the scheduler action', function () {
    Artisan::shouldReceive('call')->once()->with('backup:run')->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('');

    BackupRunState::request();
    app(PerformSiteBackupAction::class)->execute();

    expect(BackupRunState::requested())->toBeFalse()
        ->and(BackupRunState::status()['state'])->toBe('finished');
});
