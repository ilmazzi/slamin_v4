<?php

namespace App\Http\Requests\Api\V1\Media;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'photo_limit' => ['sometimes', 'integer', 'min:0'],
            'video_limit' => ['sometimes', 'integer', 'min:0'],
            'photo_max_size_kb' => ['sometimes', 'integer', 'min:1'],
            'video_max_size_kb' => ['sometimes', 'integer', 'min:1'],
            'photo_allowed_mimes' => ['sometimes', 'array'],
            'photo_allowed_mimes.*' => ['string'],
            'video_allowed_mimes' => ['sometimes', 'array'],
            'video_allowed_mimes.*' => ['string'],
            'peertube_base_url' => ['sometimes', 'nullable', 'url'],
            'peertube_admin_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'peertube_admin_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'peertube_upload_channel_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'peertube_default_privacy' => ['sometimes', 'integer', 'min:0'],
            'peertube_default_category' => ['sometimes', 'integer', 'min:0'],
            'peertube_default_licence' => ['sometimes', 'integer', 'min:0'],
            'peertube_comments_policy' => ['sometimes', 'integer', 'min:0'],
            'peertube_download_enabled' => ['sometimes', 'boolean'],
            'peertube_verify_ssl' => ['sometimes', 'boolean'],
            'stripe_secret_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_publishable_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_checkout_success_url' => ['sometimes', 'nullable', 'string', 'regex:/^https?:\/\/.+$/'],
            'stripe_checkout_cancel_url' => ['sometimes', 'nullable', 'string', 'regex:/^https?:\/\/.+$/'],
            'stripe_api_base_url' => ['sometimes', 'nullable', 'url'],
        ];
    }
}
