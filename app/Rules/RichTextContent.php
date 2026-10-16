<?php

namespace App\Rules;

use Closure;
use Domain\Shared\Support\RichTextSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;

class RichTextContent implements ValidationRule
{
    public function __construct(
        private readonly int $maxLength = 250_000,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) > $this->maxLength) {
            $fail(__('validation.max.string', ['attribute' => $attribute, 'max' => $this->maxLength]));

            return;
        }

        if (app(RichTextSanitizer::class)->toPlainText($value) === '') {
            $fail(__('validation.required', ['attribute' => $attribute]));
        }
    }
}
