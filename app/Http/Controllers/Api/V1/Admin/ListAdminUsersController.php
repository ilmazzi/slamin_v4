<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListAdminUsersRequest;
use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Support\Responses\ApiResponse;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ListAdminUsersController extends Controller
{
    public function __invoke(ListAdminUsersRequest $request): JsonResponse
    {
        $data = $request->validated();
        $search = trim((string) ($data['search'] ?? ''));

        $query = User::query()
            ->with(['profile', 'roles'])
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $builder->where(function (Builder $nested) use ($term): void {
                    $nested->where('email', 'like', $term)
                        ->orWhereHas('profile', fn (Builder $profile) => $profile
                            ->where('nickname', 'like', $term)
                            ->orWhere('display_name', 'like', $term));
                });
            })
            ->when($data['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($data['account_type'] ?? null, fn (Builder $builder, string $type) => $builder->where('account_type', $type))
            ->when(array_key_exists('verified', $data), fn (Builder $builder) => $data['verified']
                ? $builder->whereNotNull('email_verified_at')
                : $builder->whereNull('email_verified_at'))
            ->when($data['role'] ?? null, fn (Builder $builder, string $role) => $builder
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role)))
            ->latest('id')
            ->paginate((int) ($data['per_page'] ?? 20));

        return ApiResponse::success(
            data: [
                'users' => AdminUserResource::collection($query->items()),
                'roles' => Role::query()->orderBy('name')->get(['name', 'label']),
            ],
            meta: [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
            ],
        );
    }
}
