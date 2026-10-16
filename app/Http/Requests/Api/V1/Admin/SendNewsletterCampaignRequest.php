<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Rules\RichTextContent;
use Illuminate\Foundation\Http\FormRequest;

class SendNewsletterCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', new RichTextContent],
        ];
    }
}
