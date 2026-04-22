<?php

namespace App\Http\Requests\Api\V1\PoetrySlam;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPoetrySlamEntryScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.judge_number' => ['required', 'integer', 'min:1'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
        ];
    }
}
