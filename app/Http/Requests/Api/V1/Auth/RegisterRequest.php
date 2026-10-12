<?php

namespace App\Http\Requests\Api\V1\Auth;

use Domain\I18n\Actions\ResolveSupportedLocalesAction;
use Domain\User\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'nickname' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash', 'unique:profiles,nickname'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['required', new Enum(AccountType::class)],
            'preferred_locale' => ['nullable', 'string', 'max:10', Rule::in(app(ResolveSupportedLocalesAction::class)->execute())],
        ];
    }
}
