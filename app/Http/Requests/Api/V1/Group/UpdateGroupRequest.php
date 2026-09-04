<?php

namespace App\Http\Requests\Api\V1\Group;

class UpdateGroupRequest extends StoreGroupRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('remove_image')) {
            return;
        }

        $value = $this->input('remove_image');

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
                $this->merge(['remove_image' => true]);
            } elseif (in_array($normalized, ['false', '0', 'no', 'off', ''], true)) {
                $this->merge(['remove_image' => false]);
            }
        }
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }
}
