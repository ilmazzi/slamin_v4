<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Newsletter\Models\NewsletterCampaign;
use Domain\Newsletter\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;

class ListNewsletterCampaignsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $lastSentCampaign = NewsletterCampaign::query()
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->first(['sent_at']);

        $campaigns = NewsletterCampaign::query()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (NewsletterCampaign $campaign): array => [
                'id' => $campaign->public_id,
                'subject' => $campaign->subject,
                'status' => $campaign->status,
                'recipient_count' => $campaign->recipient_count,
                'sent_at' => $campaign->sent_at?->toIso8601String(),
                'created_at' => $campaign->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success([
            'subscriber_count' => NewsletterSubscriber::query()->confirmed()->count(),
            'last_sent_at' => $lastSentCampaign?->sent_at?->toIso8601String(),
            'campaigns' => $campaigns,
        ]);
    }
}
