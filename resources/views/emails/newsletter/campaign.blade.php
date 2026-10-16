@extends('emails.newsletter.layout')

@section('title', $campaign->subject)

@section('content')
    <h1 style="margin:0 0 22px;font-size:30px;line-height:1.2;">{{ $campaign->subject }}</h1>
    <div>{!! preg_replace('/data-text-align="(left|center|right|justify)"/', 'style="text-align:$1"', $campaign->content) !!}</div>
@endsection

@section('footer')
    <p style="margin:0 0 8px;">{{ __('newsletter.mail.footer_reason') }}</p>
    <p style="margin:0;">
        <a href="{{ $unsubscribeUrl }}" style="color:#666;">{{ __('newsletter.mail.unsubscribe') }}</a>
    </p>
@endsection
