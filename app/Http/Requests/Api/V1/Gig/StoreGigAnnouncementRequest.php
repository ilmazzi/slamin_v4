<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigAnnouncementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGigAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::in([
                GigAnnouncementStatus::Draft->value,
                GigAnnouncementStatus::Published->value,
                GigAnnouncementStatus::Archived->value,
            ])],
        ];
    }
}
