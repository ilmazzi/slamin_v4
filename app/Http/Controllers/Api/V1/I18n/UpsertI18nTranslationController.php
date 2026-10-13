<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\I18n\UpsertI18nTranslationRequest;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\UpsertTranslationAction;
use Domain\I18n\Exceptions\InvalidLocaleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class UpsertI18nTranslationController extends Controller
{
    public function __construct(
        private readonly UpsertTranslationAction $action,
    ) {}

    public function __invoke(UpsertI18nTranslationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $translation = $this->action->execute(
                key: $validated['key'],
                locale: $validated['locale'],
                value: $validated['value'],
                user: $request->user(),
            );
        } catch (InvalidLocaleException) {
            return ApiResponse::error(
                message: (string) __('validation.in', ['attribute' => 'locale']),
                status: 404,
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: (string) __('validation.exists', ['attribute' => 'key']),
                status: 404,
            );
        }

        return ApiResponse::success([
            'translation' => [
                'id' => $translation->id,
                'key' => $validated['key'],
                'locale' => $validated['locale'],
                'value' => $translation->value,
            ],
        ]);
    }
}
