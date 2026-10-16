<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.verification_mail_subject') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Helvetica,Arial,sans-serif;color:#000;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 32px rgba(0,0,0,.08);">
                    <tr>
                        <td style="background:#000;padding:24px 32px;">
                            <a href="{{ rtrim(config('app.frontend_url'), '/') }}" style="text-decoration:none;">
                                <img src="{{ $message->embed(resource_path('images/mail/slamin-logo.png')) }}" width="92" height="47" alt="Slam In" style="display:block;border:0;width:92px;height:47px;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 32px 36px;font-size:16px;line-height:1.6;">
                            <p style="margin:0 0 12px;color:#555;">
                                {{ __('auth.verification_mail_greeting', ['name' => $user->profile?->display_name ?: $user->profile?->nickname ?: $user->email]) }}
                            </p>
                            <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;color:#000;">
                                {{ __('auth.verification_mail_title') }}
                            </h1>
                            <p style="margin:0 0 28px;color:#333;">{{ __('auth.verification_mail_body') }}</p>
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:999px;background:#000;">
                                        <a href="{{ $verificationUrl }}" style="display:inline-block;padding:14px 24px;color:#fff;font-weight:700;text-decoration:none;">
                                            {{ __('auth.verification_mail_button') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:28px 0 0;font-size:13px;line-height:1.5;color:#666;">
                                {{ __('auth.verification_mail_expiry') }}
                            </p>
                            <p style="margin:10px 0 0;font-size:13px;line-height:1.5;color:#666;">
                                {{ __('auth.verification_mail_ignore') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #e5e5e5;font-size:12px;line-height:1.5;color:#777;">
                            {{ __('auth.verification_mail_footer') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
