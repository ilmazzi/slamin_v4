<?php

namespace App\Http\Requests\Api\V1\Gig;

use Domain\Gig\Enums\GigApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGigApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                GigApplicationStatus::Pending->value,
                GigApplicationStatus::Accepted->value,
                GigApplicationStatus::Rejected->value,
                GigApplicationStatus::Confirmed->value,
            ])],
        ];
    }
}
