<?php

namespace App\Http\Requests\Api\V1\Poetry;

use Illuminate\Foundation\Http\FormRequest;

class LikePoemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}
