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
                            <a href="{{ \Domain\Notification\Support\NotificationMailLinks::frontend() }}" style="text-decoration:none;">
                                <img src="{{ $message->embed(resource_path('images/mail/slamin-logo.png')) }}" width="92" height="47" alt="Slam In" style="display:block;border:0;width:92px;height:47px;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:16px;line-height:1.55;">
                            @yield('content')
                        </td>
                    </tr>
                    @hasSection('footer')
                        <tr>
                            <td style="padding:24px 32px;border-top:1px solid #e5e5e5;font-size:12px;line-height:1.6;color:#666;">
                                @yield('footer')
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
