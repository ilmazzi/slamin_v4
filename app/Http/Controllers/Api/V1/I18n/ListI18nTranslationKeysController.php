<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\ListTranslationKeysAction;
use Domain\I18n\Exceptions\InvalidLocaleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListI18nTranslationKeysController extends Controller
{
    public function __construct(
        private readonly ListTranslationKeysAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $locale = (string) $request->query('locale', '');
        if ($locale === '') {
            return ApiResponse::error(
                message: (string) __('validation.required', ['attribute' => 'locale']),
                status: 422,
            );
        }

        try {
            $result = $this->action->execute(
                locale: $locale,
                group: $request->query('group'),
                search: $request->query('q'),
                missingOnly: $request->boolean('missing'),
                perPage: min(100, max(1, (int) $request->query('per_page', 40))),
            );
        } catch (InvalidLocaleException) {
            return ApiResponse::error(
                message: (string) __('validation.in', ['attribute' => 'locale']),
                status: 404,
            );
        }

        $paginator = $result['keys'];

        return ApiResponse::success(
            data: [
                'keys' => $paginator->items(),
                'groups' => $result['groups'],
                'locale' => $result['locale'],
                'source_locale' => $result['source_locale'],
            ],
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
