<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('auth.smtp_test_subject') }}</title>
</head>
<body>
    <p>{{ __('auth.smtp_test_body') }}</p>
</body>
</html>
