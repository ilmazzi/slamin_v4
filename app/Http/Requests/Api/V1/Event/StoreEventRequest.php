<?php

namespace App\Http\Requests\Api\V1\Event;

use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Enums\EventRecurrenceFrequency;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'event_category_id' => [
                'nullable',
                'integer',
                Rule::exists('event_categories', 'id')->where('is_active', true),
            ],
            'festival_event_id' => ['nullable', 'integer', 'exists:events,id'],
            'schedule_mode' => ['required', Rule::in(array_column(EventScheduleMode::cases(), 'value'))],
            'delivery_mode' => ['required', Rule::in(array_column(EventDeliveryMode::cases(), 'value'))],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'has_cost' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'promo_video_url' => ['nullable', 'url', 'max:1000'],
            'registration_deadline' => ['nullable', 'date'],
            'public_invite_enabled' => ['nullable', 'boolean'],
            'audience_capacity_enabled' => ['nullable', 'boolean'],
            'audience_capacity' => ['nullable', 'integer', 'min:1'],
            'language' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in([EventStatus::Draft->value, EventStatus::Published->value])],
            'is_public' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'distinct'],
            'participant_invites' => ['nullable', 'array'],
            'participant_invites.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'participant_invites.*.name' => ['nullable', 'string', 'max:255'],
            'participant_invites.*.email' => ['nullable', 'email', 'max:255'],
            'participant_invites.*.role' => ['required_with:participant_invites', 'string', 'max:50'],
            'participant_invites.*.invite_type' => ['required_with:participant_invites', Rule::in(['internal', 'external'])],
            'participant_invites.*.status' => ['nullable', Rule::in(['pending', 'sent', 'accepted', 'declined'])],
            'audience_invite_user_ids' => ['nullable', 'array'],
            'audience_invite_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],

            'occurrence.starts_at' => ['nullable', 'date'],
            'occurrence.ends_at' => ['nullable', 'date', 'after_or_equal:occurrence.starts_at'],
            'occurrence.timezone' => ['nullable', 'string', 'max:100'],

            'recurrence.frequency' => ['nullable', Rule::in(array_column(EventRecurrenceFrequency::cases(), 'value'))],
            'recurrence.interval' => ['nullable', 'integer', 'min:1'],
            'recurrence.occurrences_count' => ['nullable', 'integer', 'min:1'],
            'recurrence.ends_at' => ['nullable', 'date'],

            'availability_poll.response_deadline' => ['nullable', 'date'],
            'availability_poll.instructions' => ['nullable', 'string'],
            'availability_options' => ['nullable', 'array'],
            'availability_options.*.starts_at' => ['required_with:availability_options', 'date'],
            'availability_options.*.ends_at' => ['nullable', 'date', 'after_or_equal:availability_options.*.starts_at'],
            'availability_options.*.timezone' => ['nullable', 'string', 'max:100'],
            'availability_options.*.description' => ['nullable', 'string', 'max:255'],

            'venue.place_name' => ['nullable', 'string', 'max:255'],
            'venue.country_code' => ['nullable', 'string', 'size:2'],
            'venue.city' => ['nullable', 'string', 'max:255'],
            'venue.postal_code' => ['nullable', 'string', 'max:20'],
            'venue.address_line' => ['nullable', 'string', 'max:255'],
            'venue.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'venue.longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'online.meeting_url' => ['nullable', 'url', 'max:1000'],
            'online.timezone' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $scheduleMode = $this->input('schedule_mode');
            $deliveryMode = $this->input('delivery_mode');
            $hasCost = filter_var($this->input('has_cost', false), FILTER_VALIDATE_BOOLEAN);
            $capacityEnabled = filter_var($this->input('audience_capacity_enabled', false), FILTER_VALIDATE_BOOLEAN);

            if (in_array($scheduleMode, [EventScheduleMode::Fixed->value, EventScheduleMode::Recurring->value], true)) {
                if (! $this->filled('occurrence.starts_at') || ! $this->filled('occurrence.timezone')) {
                    $validator->errors()->add('occurrence.starts_at', 'Occurrence start and timezone are required for fixed or recurring events.');
                }
            }

            if ($scheduleMode === EventScheduleMode::Recurring->value) {
                if (! $this->filled('recurrence.frequency') || ! $this->filled('recurrence.interval')) {
                    $validator->errors()->add('recurrence.frequency', 'Recurrence frequency and interval are required for recurring events.');
                }
            }

            if ($scheduleMode === EventScheduleMode::Availability->value) {
                if (! $this->filled('availability_poll.response_deadline')) {
                    $validator->errors()->add('availability_poll.response_deadline', 'Availability response deadline is required.');
                }

                $options = $this->input('availability_options', []);
                if (! is_array($options) || count($options) < 1) {
                    $validator->errors()->add('availability_options', 'At least one availability option is required.');
                }
            }

            if ($deliveryMode === EventDeliveryMode::InPerson->value) {
                foreach (['place_name', 'country_code', 'city'] as $field) {
                    if (! $this->filled("venue.{$field}")) {
                        $validator->errors()->add("venue.{$field}", 'Venue details are required for in-person events.');
                    }
                }
            }

            if ($deliveryMode === EventDeliveryMode::Online->value) {
                foreach (['meeting_url', 'timezone'] as $field) {
                    if (! $this->filled("online.{$field}")) {
                        $validator->errors()->add("online.{$field}", 'Online event URL and timezone are required.');
                    }
                }
            }

            if ($hasCost) {
                if (! $this->filled('price')) {
                    $validator->errors()->add('price', 'Price is required when the event has a cost.');
                }
                if (! $this->filled('currency')) {
                    $validator->errors()->add('currency', 'Currency is required when the event has a cost.');
                }
            }

            if ($capacityEnabled && ! $this->filled('audience_capacity')) {
                $validator->errors()->add('audience_capacity', 'Audience capacity is required when capacity control is enabled.');
            }
        });
    }
}
