<?php

namespace App\Http\Requests\Api\V1\Event;

class UpdateEventRequest extends StoreEventRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_cover_image' => ['nullable', 'boolean'],
        ]);
    }
}
