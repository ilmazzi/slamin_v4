<?php

namespace App\Http\Requests\Api\V1\Event;

use Domain\Event\Enums\EventStatus;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends StoreEventRequest
{
    public function rules(): array
    {
        if ($this->isStatusOnlyUpdate()) {
            return [
                'status' => ['required', Rule::in([EventStatus::Draft->value, EventStatus::Published->value])],
            ];
        }

        return array_merge(parent::rules(), [
            'remove_cover_image' => ['nullable', 'boolean'],
        ]);
    }

    public function isStatusOnlyUpdate(): bool
    {
        $extraKeys = collect($this->except(['_method']))
            ->keys()
            ->reject(fn ($key) => $key === 'status')
            ->values();

        return $this->filled('status') && $extraKeys->isEmpty();
    }
}
