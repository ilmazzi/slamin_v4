<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateAdminUserRequest;
use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Support\Responses\ApiResponse;
use Domain\User\Enums\AccountType;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAdminUserController extends Controller
{
    public function __invoke(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $roles = array_values(array_unique($data['roles']));
        $willBeActiveAdmin = $data['status'] === 'active'
            && $data['email_verified']
            && in_array('admin', $roles, true);

        if ($request->user()->is($user) && ! $willBeActiveAdmin) {
            throw ValidationException::withMessages([
                'user' => ['Non puoi rimuovere, sospendere o rendere non verificato il tuo account amministratore.'],
            ]);
        }

        if ($user->hasRole('admin') && ! $willBeActiveAdmin && ! $this->anotherActiveAdminExists($user)) {
            throw ValidationException::withMessages([
                'user' => ['Deve rimanere almeno un altro amministratore attivo e verificato.'],
            ]);
        }

        DB::transaction(function () use ($user, $data, $roles): void {
            $user->forceFill([
                'status' => $data['status'],
                'account_type' => AccountType::from($data['account_type']),
                'email_verified_at' => $data['email_verified']
                    ? ($user->email_verified_at ?? now())
                    : null,
            ])->save();

            $roleIds = Role::query()->whereIn('name', $roles)->pluck('id');
            $user->roles()->sync($roleIds);
        });

        return ApiResponse::success([
            'user' => new AdminUserResource($user->fresh(['profile', 'roles'])),
        ]);
    }

    private function anotherActiveAdminExists(User $user): bool
    {
        return User::query()
            ->whereKeyNot($user->getKey())
            ->where('status', 'active')
            ->whereNotNull('email_verified_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->exists();
    }
}
