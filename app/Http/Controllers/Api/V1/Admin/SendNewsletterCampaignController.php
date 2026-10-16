<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SendNewsletterCampaignRequest;
use App\Support\Responses\ApiResponse;
use Domain\Newsletter\Jobs\SendNewsletterCampaignJob;
use Domain\Newsletter\Models\NewsletterCampaign;
use Domain\Newsletter\Models\NewsletterSubscriber;
use Domain\Shared\Support\RichTextSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SendNewsletterCampaignController extends Controller
{
    public function __invoke(
        SendNewsletterCampaignRequest $request,
        RichTextSanitizer $sanitizer,
    ): JsonResponse {
        $campaign = Cache::lock('newsletter-campaign-send', 10)->block(5, function () use ($request, $sanitizer) {
            $hasRecentOrPendingCampaign = NewsletterCampaign::query()
                ->whereIn('status', ['queued', 'sending'])
                ->orWhere('sent_at', '>=', now()->subDays(7))
                ->exists();

            if ($hasRecentOrPendingCampaign) {
                throw ValidationException::withMessages([
                    'newsletter' => [(string) __('newsletter.admin.cooldown')],
                ]);
            }

            if (! NewsletterSubscriber::query()->confirmed()->exists()) {
                throw ValidationException::withMessages([
                    'newsletter' => [(string) __('newsletter.admin.no_subscribers')],
                ]);
            }

            return NewsletterCampaign::query()->create([
                'created_by_user_id' => $request->user()->id,
                'subject' => $request->string('subject')->trim()->toString(),
                'content' => $sanitizer->sanitize($request->string('content')->toString()),
                'status' => 'queued',
            ]);
        });

        SendNewsletterCampaignJob::dispatch($campaign->id);

        return ApiResponse::success([
            'campaign' => [
                'id' => $campaign->public_id,
                'subject' => $campaign->subject,
                'status' => $campaign->status,
                'recipient_count' => 0,
                'sent_at' => null,
                'created_at' => $campaign->created_at?->toIso8601String(),
            ],
        ], status: 202);
    }
}
