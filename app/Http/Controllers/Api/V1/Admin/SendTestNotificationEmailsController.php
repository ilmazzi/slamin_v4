<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SendTestNotificationEmailsRequest;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\SendTestNotificationEmailAction;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Throwable;

class SendTestNotificationEmailsController extends Controller
{
    public function __invoke(SendTestNotificationEmailsRequest $request, SendTestNotificationEmailAction $action): JsonResponse
    {
        $category = $request->category();
        $sender = $request->user();

        $results = User::query()
            ->with('profile')
            ->whereIn('public_id', $request->userIds())
            ->get()
            ->map(function (User $recipient) use ($action, $category, $sender): array {
                $result = [
                    'public_id' => $recipient->public_id,
                    'email' => $recipient->email,
                    'opted_out' => ! $recipient->wantsNotificationEmail($category),
                ];

                try {
                    return $result + ['sent' => true, 'uses_real_data' => $action->execute($recipient, $category, $sender)];
                } catch (Throwable $exception) {
                    report($exception);

                    return $result + ['sent' => false, 'error' => $exception->getMessage()];
                }
            })
            ->values()
            ->all();

        return ApiResponse::success([
            'mailer' => config('mail.default'),
            'results' => $results,
        ]);
    }
}
