<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Feedback\Actions\SubmitOpenProjectFeedbackAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class StoreOpenProjectFeedbackController extends Controller
{
    public function __construct(
        private readonly SubmitOpenProjectFeedbackAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'url' => ['nullable', 'string', 'max:2000'],
            'screenshot' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        try {
            $workPackageId = $this->action->execute(
                $request->user(),
                $data['subject'],
                $data['description'],
                $data['url'] ?? null,
                $request->file('screenshot'),
            );
        } catch (Throwable $exception) {
            if ($exception->getMessage() === 'openproject_not_configured') {
                return ApiResponse::error(
                    message: 'OpenProject non è configurato.',
                    status: 503,
                );
            }

            report($exception);

            return response()->json([
                'message' => $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : 'Invio del feedback non riuscito.',
                'data' => null,
                'meta' => [],
                'errors' => [
                    'message' => $exception->getMessage() !== ''
                        ? $exception->getMessage()
                        : 'Invio del feedback non riuscito.',
                    'fields' => [],
                ],
            ], 422);
        }

        return ApiResponse::success([
            'work_package_id' => $workPackageId,
        ], status: 201);
    }
}
