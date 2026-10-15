@extends('emails.notifications.layout')

@section('title', __('notifications.mail.digest.subject'))

@section('content')
    <p style="margin:0 0 16px;">{{ __('notifications.mail.greeting', ['name' => $recipient->profile?->display_name ?: $recipient->profile?->nickname]) }}</p>
    <h1 style="margin:0 0 8px;font-size:24px;line-height:1.3;">{{ __('notifications.mail.digest.title') }}</h1>
    <p style="margin:0 0 24px;color:#666;">{{ __('notifications.mail.digest.intro') }}</p>

    @foreach (['events', 'poems', 'articles', 'media'] as $section)
        @if (! empty($popular[$section]))
            <h2 style="margin:28px 0 12px;font-size:18px;border-bottom:2px solid #000;padding-bottom:6px;">{{ __('notifications.mail.digest.sections.'.$section) }}</h2>
            @foreach ($popular[$section] as $item)
                <p style="margin:0 0 10px;">
                    <a href="{{ $item['url'] }}" style="color:#000;font-weight:700;">{{ $item['title'] ?: __('media.ui.untitled') }}</a>
                    @if (! empty($item['starts_at']))
                        <br><span style="font-size:14px;color:#666;">{{ \Illuminate\Support\Carbon::parse($item['starts_at'])->timezone($item['timezone'] ?: 'Europe/Rome')->locale(app()->getLocale())->isoFormat('dddd D MMMM, HH:mm') }}</span>
                    @elseif ($item['likes'] > 0)
                        <br><span style="font-size:14px;color:#666;">{{ trans_choice('notifications.mail.likes.item_count', $item['likes'], ['count' => $item['likes']]) }}</span>
                    @endif
                </p>
            @endforeach
        @endif
    @endforeach

    <h2 style="margin:32px 0 12px;font-size:18px;border-bottom:2px solid #000;padding-bottom:6px;">{{ __('notifications.mail.digest.sections.notifications') }}</h2>
    @forelse ($notifications as $notification)
        <p style="margin:0 0 12px;">
            <a href="{{ $notification['url'] }}" style="color:#000;font-weight:700;">{{ $notification['title'] }}</a>
            <br><span style="font-size:14px;color:#444;">{{ $notification['message'] }}</span>
        </p>
    @empty
        <p style="margin:0;color:#666;">{{ __('notifications.mail.digest.no_notifications') }}</p>
    @endforelse

    @include('emails.notifications._button', ['url' => \Domain\Notification\Support\NotificationMailLinks::frontend('dashboard'), 'label' => __('notifications.mail.digest.cta')])
@endsection
