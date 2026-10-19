<?php

use Domain\Backup\Jobs\RunSiteBackupJob;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

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
            ],
        ]);
});

it('queues a backup when an admin asks to run one', function () {
    Queue::fake();
    Sanctum::actingAs(backupAdminUser());

    $this->postJson('/api/v1/admin/backups')
        ->assertOk()
        ->assertJsonPath('data.queued', true);

    Queue::assertPushed(RunSiteBackupJob::class);
});

it('forbids audience users from the backup panel', function () {
    Sanctum::actingAs(User::factory()->create([
        'email_verified_at' => now(),
    ]));

    $this->getJson('/api/v1/admin/backups')->assertForbidden();
    $this->postJson('/api/v1/admin/backups')->assertForbidden();
});
