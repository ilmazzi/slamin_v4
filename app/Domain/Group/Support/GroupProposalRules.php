<?php

namespace Domain\Group\Support;

use Domain\Group\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GroupProposalRules
{
    public static function rules(): array
    {
        return [
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'distinct', Rule::exists('groups', 'id')->where('is_active', true)],
        ];
    }

    public static function normalize(FormRequest $request): void
    {
        $value = $request->input('group_ids');
        if (! is_string($value)) {
            return;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $request->merge(['group_ids' => $decoded]);
        }
    }

    public static function validate(FormRequest $request, Validator $validator): void
    {
        if (! $request->filled('group_ids')) {
            return;
        }

        $groupIds = $request->input('group_ids', []);
        if (! is_array($groupIds)) {
            $groupIds = [];
        }

        $numericGroupIds = collect($groupIds)
            ->filter(fn ($groupId): bool => is_numeric($groupId))
            ->map(fn ($groupId): int => (int) $groupId)
            ->unique()
            ->values();

        $proposabeGroupIds = $numericGroupIds->isEmpty()
            ? collect()
            : Group::query()
                ->whereIn('id', $numericGroupIds->all())
                ->where('is_active', true)
                ->get()
                ->filter(fn (Group $group): bool => $group->canReceiveProposalFrom((int) $request->user()->id))
                ->pluck('id')
                ->map(fn ($groupId): int => (int) $groupId);

        $invalidGroupIds = collect($groupIds)
            ->filter(fn ($groupId): bool => ! is_numeric($groupId)
                || ! $proposabeGroupIds->contains((int) $groupId))
            ->values();

        if ($invalidGroupIds->isNotEmpty()) {
            $validator->errors()->add('group_ids', __('group.errors.proposal_not_allowed'));
        }
    }
}
