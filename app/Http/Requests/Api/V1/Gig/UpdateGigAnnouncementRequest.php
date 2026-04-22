<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigAnnouncementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGigAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in([
                GigAnnouncementStatus::Draft->value,
                GigAnnouncementStatus::Published->value,
                GigAnnouncementStatus::Archived->value,
            ])],
        ];
    }
}
