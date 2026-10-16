<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Mail\SupportRequestMail;
use App\Support\Responses\ApiResponse;
use Domain\Admin\Actions\ApplyMailSettingsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class StoreSupportRequestController extends Controller
{
    public function __invoke(Request $request, ApplyMailSettingsAction $applyMailSettings): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'category' => ['required', Rule::in(['technical', 'account', 'content', 'events', 'media', 'other'])],
            'subject' => ['required', 'string', 'min:3', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt,log'],
        ]);

        try {
            $applyMailSettings->execute();

            Mail::to(config('mail.support.address'))->send(new SupportRequestMail(
                requesterName: $data['name'],
                requesterEmail: $data['email'],
                category: $data['category'],
                requestSubject: $data['subject'],
                requestMessage: $data['message'],
                files: $request->file('attachments', []),
            ));
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error(__('support.send_failed'), status: 503);
        }

        return ApiResponse::success([
            'message' => __('support.sent'),
        ], status: 201);
    }
}
