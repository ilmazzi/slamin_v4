<?php

namespace App\Console\Commands;

use Domain\User\Enums\AccountType;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'user:admin
        {email : Email of the admin user}
        {--password= : Password. Required when the user does not exist yet}
        {--account-type= : audience or poet_organizer. New users default to poet_organizer}';

    protected $description = 'Create a user, or update an existing one, and assign the admin role';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email non valida.');

            return self::FAILURE;
        }

        $password = $this->option('password');
        $password = is_string($password) && $password !== '' ? $password : null;
        $accountType = $this->accountType();

        if ($accountType === false) {
            $this->error('account-type deve essere audience oppure poet_organizer.');

            return self::FAILURE;
        }

        if ($password !== null && strlen($password) < 8) {
            $this->error('La password deve avere almeno 8 caratteri.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            if ($password === null) {
                $this->error('Per un utente nuovo serve --password di almeno 8 caratteri.');

                return self::FAILURE;
            }

            $user = User::query()->create([
                'email' => $email,
                'password' => Hash::make($password),
                'account_type' => $accountType ?? AccountType::PoetOrganizer,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $this->info("Utente creato: {$user->email}");
        } else {
            if ($password !== null) {
                $user->password = Hash::make($password);
            }

            if ($accountType instanceof AccountType) {
                $user->account_type = $accountType;
            }

            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }

            if ($user->status !== 'active') {
                $user->status = 'active';
            }

            if ($user->isDirty()) {
                $user->save();
            }

            $this->info("Utente gia presente: {$user->email}");
        }

        $role = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            ['label' => 'Admin'],
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->info("{$user->email} e admin.");

        return self::SUCCESS;
    }

    private function accountType(): AccountType|false|null
    {
        $value = $this->option('account-type');

        if (! is_string($value) || $value === '') {
            return null;
        }

        return AccountType::tryFrom($value) ?? false;
    }
}
