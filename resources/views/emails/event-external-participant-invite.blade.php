<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('event.mail.external_participant_invite_subject', ['title' => $event->title]) }}</title>
</head>
<body>
    <p>{{ __('event.mail.external_participant_invite_greeting', ['name' => $invite->name ?: __('event.mail.external_participant_generic_recipient')]) }}</p>

    <p>{{ __('event.mail.external_participant_invite_intro', [
        'role' => $invite->role,
        'title' => $event->title,
    ]) }}</p>

    @if ($event->description)
        <p>{{ $event->description }}</p>
    @endif

    @if ($event->schedule_mode?->value === 'fixed' && $event->occurrences->first())
        <p>
            {{ __('event.mail.event_time_label') }}:
            {{ $event->occurrences->first()->starts_at?->timezone($event->occurrences->first()->timezone)->format('Y-m-d H:i') }}
            ({{ $event->occurrences->first()->timezone }})
        </p>
    @endif

    @if ($event->delivery_mode?->value === 'in_person' && $event->venueDetail)
        <p>
            {{ __('event.mail.event_place_label') }}:
            {{ $event->venueDetail->place_name }},
            {{ $event->venueDetail->city }}
        </p>
    @endif

    @if ($event->delivery_mode?->value === 'online' && $event->onlineDetail)
        <p>
            {{ __('event.mail.event_online_label') }}:
            {{ $event->onlineDetail->meeting_url }}
        </p>
    @endif

    <p>{{ __('event.mail.external_participant_invite_outro') }}</p>
</body>
</html>
