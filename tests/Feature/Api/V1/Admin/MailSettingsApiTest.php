<?php

use App\Mail\SmtpTestMail;
use Domain\Admin\Models\MailSetting;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function mailSettingsAdmin(): User
{
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin'],
    );
    $admin->roles()->sync([$role->id]);

    return $admin;
}

it('allows admins to save smtp settings without exposing the password', function () {
    Sanctum::actingAs(mailSettingsAdmin());

    $this->patchJson('/api/v1/admin/mail-settings', [
        'enabled' => true,
        'host' => 'smtp.example.test',
        'port' => 587,
        'scheme' => 'smtp',
        'username' => 'mailer',
        'password' => 'smtp-secret',
        'from_address' => 'noreply@example.test',
        'from_name' => 'Slam In',
    ])
        ->assertOk()
        ->assertJsonPath('data.settings.enabled', true)
        ->assertJsonPath('data.settings.password', null)
        ->assertJsonPath('data.settings.password_set', true);

    $settings = MailSetting::query()->firstOrFail();

    expect($settings->password)->toBe('smtp-secret')
        ->and($settings->getRawOriginal('password'))->not->toContain('smtp-secret');

    $this->getJson('/api/v1/admin/mail-settings')
        ->assertOk()
        ->assertJsonPath('data.settings.host', 'smtp.example.test')
        ->assertJsonPath('data.settings.password', null)
        ->assertJsonPath('data.settings.password_set', true);
});

it('keeps the stored smtp password when an empty password is submitted', function () {
    Sanctum::actingAs(mailSettingsAdmin());

    MailSetting::query()->create([
        'enabled' => true,
        'host' => 'smtp.example.test',
        'port' => 587,
        'scheme' => 'smtp',
        'username' => 'mailer',
        'password' => 'smtp-secret',
        'from_address' => 'noreply@example.test',
    ]);

    $this->patchJson('/api/v1/admin/mail-settings', [
        'enabled' => true,
        'host' => 'smtp.changed.test',
        'port' => 587,
        'scheme' => 'smtp',
        'username' => 'mailer',
        'password' => '',
        'from_address' => 'noreply@example.test',
    ])->assertOk();

    expect(MailSetting::query()->firstOrFail()->password)->toBe('smtp-secret');
});

it('sends a test message through the configured mailer', function () {
    Mail::fake();
    Sanctum::actingAs(mailSettingsAdmin());

    MailSetting::query()->create([
        'enabled' => true,
        'host' => 'smtp.example.test',
        'port' => 587,
        'scheme' => 'smtp',
        'from_address' => 'noreply@example.test',
        'from_name' => 'Slam In',
    ]);

    $this->postJson('/api/v1/admin/mail-settings/test', [
        'email' => 'admin@example.test',
    ])->assertOk();

    Mail::assertSent(SmtpTestMail::class, fn (SmtpTestMail $mail): bool => $mail->hasTo('admin@example.test'));
});

it('forbids non admins from reading smtp settings', function () {
    Sanctum::actingAs(User::factory()->create(['email_verified_at' => now()]));

    $this->getJson('/api/v1/admin/mail-settings')->assertForbidden();
});
