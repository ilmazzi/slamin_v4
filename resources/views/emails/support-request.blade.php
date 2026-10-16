<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('support.mail_subject', ['subject' => $requestSubject]) }}</title>
</head>
<body>
    <h1>{{ __('support.mail_intro') }}</h1>
    <p><strong>{{ __('support.mail_name') }}:</strong> {{ $requesterName }}</p>
    <p><strong>{{ __('support.mail_email') }}:</strong> {{ $requesterEmail }}</p>
    <p><strong>{{ __('support.mail_category') }}:</strong> {{ __('support.categories.'.$category) }}</p>
    <p><strong>{{ __('support.mail_subject_label') }}:</strong> {{ $requestSubject }}</p>
    <p><strong>{{ __('support.mail_message') }}:</strong></p>
    <p>{!! nl2br(e($requestMessage)) !!}</p>
</body>
</html>
