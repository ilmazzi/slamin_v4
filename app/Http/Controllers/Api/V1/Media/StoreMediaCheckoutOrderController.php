<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\StoreMediaCheckoutOrderRequest;
use App\Http\Resources\Api\V1\Media\MediaCheckoutOrderResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\StoreMediaCheckoutOrderAction;
use Domain\Media\DTOs\StoreMediaCheckoutOrderData;
use Illuminate\Http\JsonResponse;

class StoreMediaCheckoutOrderController extends Controller
{
    public function __construct(
        private readonly StoreMediaCheckoutOrderAction $action,
    ) {
    }

    public function __invoke(StoreMediaCheckoutOrderRequest $request): JsonResponse
    {
        $order = $this->action->execute(
            $request->user()->id,
            StoreMediaCheckoutOrderData::fromRequest($request),
        );

        return ApiResponse::success([
            'order' => new MediaCheckoutOrderResource($order),
        ], status: 201);
    }
}
