<?php

namespace App\Http\Requests\Api\V1\Group;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupAffiliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'child_group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $parentId = $this->input('parent_group_id');
            $childId = $this->input('child_group_id');
            $hasParent = filled($parentId);
            $hasChild = filled($childId);

            if ($hasParent === $hasChild) {
                $validator->errors()->add('group', __('group.errors.affiliation_invalid'));
            }
        });
    }
}
