<?php

namespace App\Http\Requests\Api\V1\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:5000', 'required_without:attachments'],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:chat_messages,id'],
            'attachments' => ['nullable', 'array', 'required_without:content'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
