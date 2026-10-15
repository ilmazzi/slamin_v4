<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Helvetica,Arial,sans-serif;color:#000;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;">
                    <tr>
                        <td style="background:#000;padding:24px 32px;">
                            <a href="{{ \Domain\Notification\Support\NotificationMailLinks::frontend() }}" style="color:#fff;text-decoration:none;font-size:26px;font-weight:800;font-style:italic;letter-spacing:-0.5px;">SLAM <span style="color:#e30613;">IN</span></a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:16px;line-height:1.5;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;border-top:1px solid #e5e5e5;font-size:12px;line-height:1.6;color:#666;">
                            <p style="margin:0 0 8px;">{{ __('notifications.mail.footer_reason', ['category' => __('notifications.preferences.categories.'.$category->value.'.label')]) }}</p>
                            <p style="margin:0;">
                                <a href="{{ $unsubscribeUrl }}" style="color:#666;">{{ __('notifications.mail.unsubscribe') }}</a>
                                &nbsp;·&nbsp;
                                <a href="{{ $preferencesUrl }}" style="color:#666;">{{ __('notifications.mail.manage') }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
