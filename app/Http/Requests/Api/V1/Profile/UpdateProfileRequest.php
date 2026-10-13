<?php

namespace App\Http\Requests\Api\V1\Profile;

use Domain\I18n\Actions\ResolveSupportedLocalesAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        $visibility = $this->input('visibility');
        if (is_string($visibility)) {
            $decoded = json_decode($visibility, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $updates['visibility'] = $decoded;
            }
        }

        foreach (['is_public', 'remove_avatar', 'remove_banner'] as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsed !== null) {
                $updates[$field] = $parsed;
            }
        }

        if ($visibilityNested = $this->input('visibility')) {
            if (is_array($visibilityNested)) {
                $normalized = [];
                foreach (['bio', 'location', 'website_url', 'email'] as $key) {
                    if (! array_key_exists($key, $visibilityNested)) {
                        continue;
                    }
                    $value = $visibilityNested[$key];
                    if (is_string($value)) {
                        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        $normalized[$key] = $parsed ?? (bool) $value;
                    } else {
                        $normalized[$key] = (bool) $value;
                    }
                }
                $updates['visibility'] = $normalized;
            }
        }

        foreach (['website_url', 'bio', 'location', 'display_name'] as $nullable) {
            if ($this->exists($nullable) && $this->input($nullable) === '') {
                $updates[$nullable] = null;
            }
        }

        $website = array_key_exists('website_url', $updates)
            ? $updates['website_url']
            : $this->input('website_url');
        if (is_string($website) && $website !== '' && ! preg_match('#^https?://#i', $website)) {
            $updates['website_url'] = 'https://'.$website;
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }

    public function rules(): array
    {
        $profileId = $this->user()?->profile()->value('id');

        return [
            'nickname' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                Rule::unique('profiles', 'nickname')->ignore($profileId),
            ],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'website_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'preferred_locale' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::in(app(ResolveSupportedLocalesAction::class)->execute()),
            ],
            'is_public' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'array'],
            'visibility.bio' => ['sometimes', 'boolean'],
            'visibility.location' => ['sometimes', 'boolean'],
            'visibility.website_url' => ['sometimes', 'boolean'],
            'visibility.email' => ['sometimes', 'boolean'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'banner' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_banner' => ['sometimes', 'boolean'],
        ];
    }
}
