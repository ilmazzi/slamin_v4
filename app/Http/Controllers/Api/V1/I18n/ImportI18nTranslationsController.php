<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\I18n\ImportI18nTranslationsRequest;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\ImportTranslationsAction;
use Domain\I18n\DTOs\ImportTranslationsData;
use Domain\I18n\Exceptions\InvalidLocaleException;
use Illuminate\Http\JsonResponse;

class ImportI18nTranslationsController extends Controller
{
    public function __construct(
        private readonly ImportTranslationsAction $action,
    ) {}

    public function __invoke(ImportI18nTranslationsRequest $request): JsonResponse
    {
        $data = ImportTranslationsData::fromRequest($request);
        if (! $request->user()?->hasRole('admin')) {
            $data = new ImportTranslationsData(
                locale: $data->locale,
                translations: $data->translations,
                overwrite: $data->overwrite,
                createKeys: false,
            );
        }

        try {
            $result = $this->action->execute($data, $request->user());
        } catch (InvalidLocaleException) {
            return ApiResponse::error(
                message: (string) __('validation.in', ['attribute' => 'locale']),
                status: 404,
            );
        }

        return ApiResponse::success($result);
    }
}
