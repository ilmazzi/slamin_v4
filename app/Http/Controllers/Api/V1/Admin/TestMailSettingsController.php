<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SmtpTestMail;
use App\Support\Responses\ApiResponse;
use Domain\Admin\Actions\ApplyMailSettingsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class TestMailSettingsController extends Controller
{
    public function __invoke(Request $request, ApplyMailSettingsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $settings = $action->execute();

        if (! $settings?->enabled) {
            throw ValidationException::withMessages([
                'email' => [__('auth.smtp_not_enabled')],
            ]);
        }

        try {
            Mail::to($validated['email'])->send(new SmtpTestMail);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'email' => [__('auth.smtp_test_failed', ['message' => $exception->getMessage()])],
            ]);
        }

        return ApiResponse::success([
            'message' => __('auth.smtp_test_sent'),
        ]);
    }
}
