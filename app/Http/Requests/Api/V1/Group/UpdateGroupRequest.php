<?php

namespace App\Http\Requests\Api\V1\Group;

class UpdateGroupRequest extends StoreGroupRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }
}
