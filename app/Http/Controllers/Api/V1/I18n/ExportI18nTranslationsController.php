<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\I18n\ExportI18nTranslationsRequest;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\ExportTranslationsAction;
use Domain\I18n\Exceptions\InvalidLocaleException;
use Illuminate\Http\JsonResponse;

class ExportI18nTranslationsController extends Controller
{
    public function __construct(
        private readonly ExportTranslationsAction $action,
    ) {}

    public function __invoke(ExportI18nTranslationsRequest $request): JsonResponse
    {
        try {
            $export = $this->action->execute((string) $request->validated('locale'));
        } catch (InvalidLocaleException) {
            return ApiResponse::error(
                message: (string) __('validation.in', ['attribute' => 'locale']),
                status: 404,
            );
        }

        return ApiResponse::success($export);
    }
}
