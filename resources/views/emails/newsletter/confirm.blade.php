@extends('emails.newsletter.layout')

@section('title', __('newsletter.mail.confirm_subject'))

@section('content')
    <h1 style="margin:0 0 18px;font-size:28px;line-height:1.2;">{{ __('newsletter.mail.confirm_title') }}</h1>
    <p style="margin:0 0 24px;">{{ __('newsletter.mail.confirm_body') }}</p>
    <p style="margin:0;">
        <a href="{{ $confirmationUrl }}" style="display:inline-block;padding:13px 22px;border-radius:999px;background:#ed0014;color:#fff;font-weight:700;text-decoration:none;">
            {{ __('newsletter.mail.confirm_cta') }}
        </a>
    </p>
@endsection

@section('footer')
    <p style="margin:0;">{{ __('newsletter.mail.confirm_ignore') }}</p>
@endsection
