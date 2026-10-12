<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\I18n\StoreI18nLanguageRequest;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\StoreLanguageAction;
use Illuminate\Http\JsonResponse;

class StoreI18nLanguageController extends Controller
{
    public function __construct(
        private readonly StoreLanguageAction $action,
    ) {}

    public function __invoke(StoreI18nLanguageRequest $request): JsonResponse
    {
        $language = $this->action->execute($request->validated());

        return ApiResponse::success([
            'language' => [
                'id' => $language->id,
                'code' => $language->code,
                'name' => $language->name,
                'flag' => $language->flag,
                'is_active' => $language->is_active,
                'is_default' => $language->is_default,
                'sort_order' => $language->sort_order,
            ],
        ], status: 201);
    }
}
