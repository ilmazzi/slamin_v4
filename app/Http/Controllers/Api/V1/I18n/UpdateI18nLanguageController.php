<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\I18n\UpdateI18nLanguageRequest;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\UpdateLanguageAction;
use Domain\I18n\Models\Language;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class UpdateI18nLanguageController extends Controller
{
    public function __construct(
        private readonly UpdateLanguageAction $action,
    ) {}

    public function __invoke(UpdateI18nLanguageRequest $request, Language $language): JsonResponse
    {
        try {
            $language = $this->action->execute($language, $request->validated());
        } catch (InvalidArgumentException) {
            return ApiResponse::error(
                message: (string) __('validation.in', ['attribute' => 'language']),
                status: 422,
            );
        }

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
        ]);
    }
}
