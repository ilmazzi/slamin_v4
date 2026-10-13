<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\I18n\StoreI18nTranslationKeyRequest;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\StoreTranslationKeyAction;
use Illuminate\Http\JsonResponse;

class StoreI18nTranslationKeyController extends Controller
{
    public function __construct(
        private readonly StoreTranslationKeyAction $action,
    ) {}

    public function __invoke(StoreI18nTranslationKeyRequest $request): JsonResponse
    {
        $key = $this->action->execute($request->validated(), $request->user());

        return ApiResponse::success([
            'key' => [
                'id' => $key->id,
                'key' => $key->key,
                'group' => $key->group,
                'notes' => $key->notes,
            ],
        ], status: 201);
    }
}
