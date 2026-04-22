<?php

namespace App\Http\Requests\Api\V1\Media;

use Domain\Media\Enums\MediaSnapKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaSnapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'timestamp_seconds' => ['sometimes', 'integer', 'min:0'],
            'kind' => ['sometimes', Rule::in(array_column(MediaSnapKind::cases(), 'value'))],
            'comment' => ['sometimes', 'string', 'max:500'],
        ];
    }
}
