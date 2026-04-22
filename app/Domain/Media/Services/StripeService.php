<?php

namespace Domain\Media\Services;

use Domain\Media\Actions\ResolveMediaSettingsAction;
use Domain\Media\Models\MediaCheckoutOrder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeService
{
    public function __construct(
        private readonly ResolveMediaSettingsAction $resolveMediaSettingsAction,
    ) {
    }

    public function isConfigured(): bool
    {
        $settings = $this->resolveMediaSettingsAction->execute();

        return filled($settings['stripe_secret_key'])
            && filled($settings['stripe_webhook_secret'])
            && filled($settings['stripe_checkout_success_url'])
            && filled($settings['stripe_checkout_cancel_url']);
    }

    /**
     * @return array<string,mixed>
     */
    public function createCheckoutSession(MediaCheckoutOrder $order): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(__('media.validation.stripe_not_configured'));
        }

        $settings = $this->resolveMediaSettingsAction->execute();
        $successUrl = $this->interpolateCheckoutUrl(
            (string) $settings['stripe_checkout_success_url'],
            $order,
        );
        $cancelUrl = $this->interpolateCheckoutUrl(
            (string) $settings['stripe_checkout_cancel_url'],
            $order,
        );

        $response = $this->baseRequest()
            ->asForm()
            ->post($this->getApiBaseUrl() . '/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $order->id,
                'customer_email' => $order->user?->email,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'unit_amount' => $order->amount,
                        'product_data' => [
                            'name' => $order->feature_name ?? $order->feature_code ?? 'Media feature',
                            'description' => $order->featurePack?->description,
                        ],
                    ],
                ]],
                'metadata' => [
                    'media_checkout_order_id' => (string) $order->id,
                    'user_id' => (string) $order->user_id,
                    'media_feature_pack_id' => (string) ($order->media_feature_pack_id ?? ''),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: __('media.validation.stripe_checkout_session_failed'));
        }

        return $response->json();
    }

    public function verifyWebhookSignature(string $payload, ?string $signatureHeader): bool
    {
        $settings = $this->resolveMediaSettingsAction->execute();
        $secret = (string) ($settings['stripe_webhook_secret'] ?? '');

        if ($secret === '' || empty($signatureHeader)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function getApiBaseUrl(): string
    {
        $settings = $this->resolveMediaSettingsAction->execute();

        return rtrim((string) ($settings['stripe_api_base_url'] ?? 'https://api.stripe.com'), '/');
    }

    private function baseRequest(): PendingRequest
    {
        $settings = $this->resolveMediaSettingsAction->execute();

        return Http::withBasicAuth((string) $settings['stripe_secret_key'], '')
            ->timeout(60);
    }

    private function interpolateCheckoutUrl(string $url, MediaCheckoutOrder $order): string
    {
        return str_replace(
            ['{CHECKOUT_ORDER_ID}', '{CHECKOUT_ORDER_UUID}'],
            [(string) $order->id, (string) $order->id],
            $url,
        );
    }
}
