<?php

namespace App\Http\Controllers\Api\V1\I18n;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\I18n\Actions\ListLanguagesAction;
use Illuminate\Http\JsonResponse;

class ListI18nLanguagesController extends Controller
{
    public function __construct(
        private readonly ListLanguagesAction $action,
    ) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'languages' => $this->action->execute(),
        ]);
    }
}
