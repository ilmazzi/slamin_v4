<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('notifications.mail.unsubscribed_title') }}</title>
</head>
<body style="margin:0;padding:48px 16px;background:#f4f4f4;font-family:Helvetica,Arial,sans-serif;color:#000;">
    <div style="max-width:520px;margin:0 auto;background:#fff;padding:32px;">
        <h1 style="margin:0 0 12px;font-size:24px;">{{ __('notifications.mail.unsubscribed_title') }}</h1>
        <p style="margin:0 0 24px;line-height:1.5;">{{ __('notifications.mail.unsubscribed_message', ['category' => $categoryLabel]) }}</p>
        <a href="{{ $preferencesUrl }}" style="display:inline-block;padding:12px 28px;background:#e30613;color:#fff;text-decoration:none;font-weight:700;border-radius:999px;">{{ __('notifications.mail.manage') }}</a>
    </div>
</body>
</html>
