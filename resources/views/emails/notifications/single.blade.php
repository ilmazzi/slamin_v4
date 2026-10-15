@extends('emails.notifications.layout')

@section('title', $notification->title)

@section('content')
    <p style="margin:0 0 16px;">{{ __('notifications.mail.greeting', ['name' => $recipient->profile?->display_name ?: $recipient->profile?->nickname]) }}</p>
    <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;">{{ $notification->title }}</h1>
    <p style="margin:0;">{{ $notification->message }}</p>
    @include('emails.notifications._button', ['url' => $actionUrl, 'label' => __('notifications.mail.open')])
@endsection
