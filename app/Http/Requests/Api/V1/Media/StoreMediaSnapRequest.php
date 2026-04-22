<?php

namespace App\Http\Requests\Api\V1\Media;

use Domain\Media\Enums\MediaSnapKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaSnapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'timestamp_seconds' => ['required', 'integer', 'min:0'],
            'kind' => ['required', Rule::in(array_column(MediaSnapKind::cases(), 'value'))],
            'comment' => ['required', 'string', 'max:500'],
        ];
    }
}
