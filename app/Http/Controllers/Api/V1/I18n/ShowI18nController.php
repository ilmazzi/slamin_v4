<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\GetUiTranslationBundleAction;
use Domain\I18n\Exceptions\InvalidLocaleException;
use Illuminate\Http\JsonResponse;

class ShowI18nController extends Controller
{
    public function __construct(
        private readonly GetUiTranslationBundleAction $getUiTranslationBundle,
    ) {}

    public function __invoke(string $locale): JsonResponse
    {
        try {
            $data = $this->getUiTranslationBundle->execute($locale);
        } catch (InvalidLocaleException) {
            return ApiResponse::error(
                message: (string) __('validation.in', ['attribute' => 'locale']),
                status: 404,
            );
        }

        return ApiResponse::success($data);
    }
}
