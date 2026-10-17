<?php

namespace App\Http\Requests\Api\V1\Event;

use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Enums\EventRecurrenceFrequency;
use Domain\Event\Enums\EventScheduleMode;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\GroupMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    private const FESTIVAL_CATEGORY_SLUG = 'festival';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /**
         * When the frontend sends multipart/form-data (e.g. with cover_image),
         * nested arrays/objects may arrive as JSON-encoded strings.
         * Normalize them back into arrays so validation + withValidator foreach() are safe.
         */
        $jsonFields = [
            'occurrence',
            'recurrence',
            'availability_poll',
            'availability_options',
            'venue',
            'online',
            'participant_invites',
            'audience_invites',
            'gigs',
            'group_ids',
            'audience_invite_user_ids',
        ];

        $updates = [];

        foreach ($jsonFields as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                $updates[$field] = $decoded;
            }
        }

        $booleanFields = [
            'has_cost',
            'public_invite_enabled',
            'audience_capacity_enabled',
            'is_public',
            'remove_cover_image',
        ];

        foreach ($booleanFields as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsed !== null) {
                $updates[$field] = $parsed;
            }
        }

        // If nested structures were JSON-decoded, normalize their booleans too.
        if (isset($updates['gigs']) && is_array($updates['gigs'])) {
            $updates['gigs'] = collect($updates['gigs'])->map(function ($gig) {
                if (! is_array($gig)) {
                    return $gig;
                }

                // Normalize empty date strings to null so Postgres timestamp casts don't choke.
                foreach (['deadline'] as $dateField) {
                    if (array_key_exists($dateField, $gig) && $gig[$dateField] === '') {
                        $gig[$dateField] = null;
                    }
                }

                foreach ([
                    'hospitality_required',
                    'cachet_enabled',
                    'travel_expenses_enabled',
                    'accommodation_enabled',
                ] as $field) {
                    if (array_key_exists($field, $gig) && is_string($gig[$field])) {
                        $parsed = filter_var($gig[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($parsed !== null) {
                            $gig[$field] = $parsed;
                        }
                    }
                }

                return $gig;
            })->all();
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
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
            'group_ids.*' => ['integer', 'distinct', Rule::exists('groups', 'id')->where('is_active', true)],
            'participant_invites' => ['nullable', 'array'],
            'participant_invites.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'participant_invites.*.name' => ['nullable', 'string', 'max:255'],
            'participant_invites.*.email' => ['nullable', 'email', 'max:255'],
            'participant_invites.*.role' => ['required_with:participant_invites', 'string', 'max:50'],
            'participant_invites.*.invite_type' => ['required_with:participant_invites', Rule::in(['internal', 'external'])],
            'participant_invites.*.status' => ['nullable', Rule::in(['pending', 'sent', 'accepted', 'declined'])],
            'audience_invite_user_ids' => ['nullable', 'array'],
            'audience_invite_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'audience_invites' => ['nullable', 'array'],
            'audience_invites.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'audience_invites.*.name' => ['nullable', 'string', 'max:255'],
            'audience_invites.*.email' => ['nullable', 'email', 'max:255'],
            'audience_invites.*.invite_type' => ['required_with:audience_invites', Rule::in(['internal', 'external'])],
            'audience_invites.*.status' => ['nullable', Rule::in(['pending', 'sent', 'accepted', 'declined'])],
            'gigs' => ['nullable', 'array'],
            'gigs.*.type' => ['required_with:gigs', 'string', Rule::in([
                GigType::PoetArtist->value,
                GigType::McHost->value,
                GigType::TechnicalSupport->value,
                GigType::Volunteer->value,
            ])],
            'gigs.*.quantity' => ['required_with:gigs', 'integer', 'min:1'],
            'gigs.*.language' => ['nullable', 'string', 'max:255'],
            'gigs.*.hospitality_required' => ['nullable', 'boolean'],
            'gigs.*.description' => ['nullable', 'string'],
            'gigs.*.proposed_price' => ['nullable', 'numeric', 'min:0'],
            'gigs.*.currency' => ['nullable', 'string', 'size:3'],
            'gigs.*.deadline' => ['nullable', 'date'],
            'gigs.*.special_requirements' => ['nullable', 'string'],
            'gigs.*.cachet_enabled' => ['nullable', 'boolean'],
            'gigs.*.travel_expenses_enabled' => ['nullable', 'boolean'],
            'gigs.*.travel_expenses_max' => ['nullable', 'numeric', 'min:0'],
            'gigs.*.accommodation_enabled' => ['nullable', 'boolean'],
            'gigs.*.accommodation_details' => ['nullable', 'string'],
            'gigs.*.status' => ['nullable', 'string', Rule::in([
                GigStatus::Draft->value,
                GigStatus::Open->value,
                GigStatus::Closed->value,
                GigStatus::Cancelled->value,
            ])],

            'occurrence.starts_at' => ['nullable', 'date'],
            'occurrence.ends_at' => ['nullable', 'date', 'after_or_equal:occurrence.starts_at'],
            'occurrence.timezone' => ['nullable', 'string', 'max:100'],

            'recurrence.frequency' => ['nullable', Rule::in(array_column(EventRecurrenceFrequency::cases(), 'value'))],
            'recurrence.interval' => ['nullable', 'integer', 'min:1'],
            'recurrence.occurrences_count' => ['nullable', 'integer', 'min:1', 'max:365'],
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
                    $validator->errors()->add('occurrence.starts_at', __('event.validation.occurrence_required'));
                }
            }

            if ($scheduleMode === EventScheduleMode::Recurring->value) {
                if (! $this->filled('recurrence.frequency') || ! $this->filled('recurrence.interval')) {
                    $validator->errors()->add('recurrence.frequency', __('event.validation.recurrence_required'));
                }
            }

            if ($scheduleMode === EventScheduleMode::Availability->value) {
                if (! $this->filled('availability_poll.response_deadline')) {
                    $validator->errors()->add('availability_poll.response_deadline', __('event.validation.availability_deadline_required'));
                }

                $options = $this->input('availability_options', []);
                if (! is_array($options) || count($options) < 1) {
                    $validator->errors()->add('availability_options', __('event.validation.availability_options_required'));
                }
            }

            if ($deliveryMode === EventDeliveryMode::InPerson->value) {
                foreach (['place_name', 'country_code', 'city'] as $field) {
                    if (! $this->filled("venue.{$field}")) {
                        $validator->errors()->add("venue.{$field}", __('event.validation.venue_required'));
                    }
                }
            }

            if ($deliveryMode === EventDeliveryMode::Online->value) {
                foreach (['meeting_url', 'timezone'] as $field) {
                    if (! $this->filled("online.{$field}")) {
                        $validator->errors()->add("online.{$field}", __('event.validation.online_required'));
                    }
                }
            }

            if ($hasCost) {
                if (! $this->filled('price')) {
                    $validator->errors()->add('price', __('event.validation.price_required'));
                }
                if (! $this->filled('currency')) {
                    $validator->errors()->add('currency', __('event.validation.currency_required'));
                }
            }

            if ($capacityEnabled && ! $this->filled('audience_capacity')) {
                $validator->errors()->add('audience_capacity', __('event.validation.audience_capacity_required'));
            }

            $participantInvites = $this->input('participant_invites', []);
            if (! is_array($participantInvites)) {
                $participantInvites = [];
            }

            foreach ($participantInvites as $index => $invite) {
                $inviteType = $invite['invite_type'] ?? null;

                if ($inviteType === 'internal' && empty($invite['user_id'])) {
                    $validator->errors()->add(
                        "participant_invites.{$index}.user_id",
                        __('event.validation.internal_participant_user_required')
                    );
                }

                if ($inviteType === 'external') {
                    if (empty($invite['email'])) {
                        $validator->errors()->add(
                            "participant_invites.{$index}.email",
                            __('event.validation.external_participant_email_required')
                        );
                    }

                    if (empty($invite['name'])) {
                        $validator->errors()->add(
                            "participant_invites.{$index}.name",
                            __('event.validation.external_participant_name_required')
                        );
                    }
                }
            }

            $audienceInvites = $this->input('audience_invites', []);
            if (! is_array($audienceInvites)) {
                $audienceInvites = [];
            }

            foreach ($audienceInvites as $index => $invite) {
                $inviteType = $invite['invite_type'] ?? null;

                if ($inviteType === 'internal' && empty($invite['user_id'])) {
                    $validator->errors()->add(
                        "audience_invites.{$index}.user_id",
                        __('event.validation.internal_audience_user_required')
                    );
                }

                if ($inviteType === 'external') {
                    if (empty($invite['email'])) {
                        $validator->errors()->add(
                            "audience_invites.{$index}.email",
                            __('event.validation.external_audience_email_required')
                        );
                    }

                    if (empty($invite['name'])) {
                        $validator->errors()->add(
                            "audience_invites.{$index}.name",
                            __('event.validation.external_audience_name_required')
                        );
                    }
                }
            }

            if ($this->filled('group_ids')) {
                $groupIds = $this->input('group_ids', []);
                if (! is_array($groupIds)) {
                    $groupIds = [];
                }

                $numericGroupIds = collect($groupIds)
                    ->filter(fn ($groupId): bool => is_numeric($groupId))
                    ->map(fn ($groupId): int => (int) $groupId)
                    ->unique()
                    ->values();

                $manageableGroupIds = $numericGroupIds->isEmpty()
                    ? collect()
                    : GroupMember::query()
                        ->whereIn('group_id', $numericGroupIds->all())
                        ->where('user_id', $this->user()->id)
                        ->where('status', GroupMemberStatus::Active)
                        ->whereIn('role', [
                            GroupMemberRole::Owner,
                            GroupMemberRole::Admin,
                            GroupMemberRole::Moderator,
                        ])
                        ->pluck('group_id')
                        ->map(fn ($groupId): int => (int) $groupId);

                $invalidGroupIds = collect($groupIds)
                    ->filter(fn ($groupId): bool => ! is_numeric($groupId)
                        || ! $manageableGroupIds->contains((int) $groupId))
                    ->values();

                if ($invalidGroupIds->isNotEmpty()) {
                    $validator->errors()->add('group_ids', __('event.validation.group_management_required'));
                }
            }

            $festivalEventId = $this->input('festival_event_id');
            $currentEventId = $this->route('event')?->id;

            if ($festivalEventId && $currentEventId && (int) $festivalEventId === (int) $currentEventId) {
                $validator->errors()->add(
                    'festival_event_id',
                    __('event.validation.festival_self_link_not_allowed')
                );

                return;
            }

            if ($festivalEventId !== null && $festivalEventId !== '') {
                $festivalEvent = Event::query()
                    ->with('category')
                    ->find($festivalEventId);

                if (! $festivalEvent || $festivalEvent->category?->slug !== self::FESTIVAL_CATEGORY_SLUG) {
                    $validator->errors()->add(
                        'festival_event_id',
                        __('event.validation.festival_parent_must_be_festival')
                    );
                }
            }

            $eventCategoryId = $this->input('event_category_id');

            if ($festivalEventId !== null && $festivalEventId !== '' && $eventCategoryId !== null && $eventCategoryId !== '') {
                $selectedCategory = EventCategory::query()->find($eventCategoryId);

                if ($selectedCategory && $selectedCategory->slug === self::FESTIVAL_CATEGORY_SLUG) {
                    $validator->errors()->add(
                        'festival_event_id',
                        __('event.validation.festival_cannot_have_parent_festival')
                    );

                    return;
                }
            }

        });
    }
}
