@extends('emails.notifications.layout')

@section('title', __('notifications.mail.chat.subject', ['name' => $senderName]))

@section('content')
    <p style="margin:0 0 16px;">{{ __('notifications.mail.greeting', ['name' => $recipient->profile?->display_name ?: $recipient->profile?->nickname]) }}</p>
    <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;">
        @if ($threadTitle)
            {{ __('notifications.mail.chat.intro_group', ['name' => $senderName, 'thread' => $threadTitle]) }}
        @else
            {{ __('notifications.mail.chat.intro', ['name' => $senderName]) }}
        @endif
    </h1>
    @if ($excerpt !== '')
        <p style="margin:0;padding:16px;background:#f4f4f4;border-left:4px solid #e30613;white-space:pre-line;">{{ $excerpt }}</p>
    @else
        <p style="margin:0;color:#666;">{{ __('notifications.mail.chat.attachment_only') }}</p>
    @endif
    @include('emails.notifications._button', ['url' => $actionUrl, 'label' => __('notifications.mail.chat.reply')])
@endsection
