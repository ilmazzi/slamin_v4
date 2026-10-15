@extends('emails.notifications.layout')

@section('title', trans_choice('notifications.mail.likes.subject', $total, ['count' => $total]))

@section('content')
    <p style="margin:0 0 16px;">{{ __('notifications.mail.greeting', ['name' => $recipient->profile?->display_name ?: $recipient->profile?->nickname]) }}</p>
    <h1 style="margin:0 0 20px;font-size:22px;line-height:1.3;">{{ trans_choice('notifications.mail.likes.intro', $total, ['count' => $total]) }}</h1>
    @foreach ($items as $item)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 12px;border:1px solid #e5e5e5;">
            <tr>
                <td style="padding:14px 16px;">
                    <a href="{{ $item['url'] }}" style="color:#000;font-weight:700;text-decoration:none;">{{ $item['title'] ?: __('media.ui.untitled') }}</a>
                    <div style="margin-top:4px;font-size:14px;color:#666;">
                        {{ trans_choice('notifications.mail.likes.item_count', $item['count'], ['count' => $item['count']]) }}@if (count($item['names'])) · {{ implode(', ', $item['names']) }}@endif
                    </div>
                </td>
            </tr>
        </table>
    @endforeach
@endsection
