<?php

return [
    'validation' => [
        'photo_limit_reached' => 'You reached the photo upload limit.',
        'video_limit_reached' => 'You reached the video upload limit.',
        'photo_too_large' => 'The photo exceeds the maximum allowed size.',
        'video_too_large' => 'The video exceeds the maximum allowed size.',
        'photo_mime_not_allowed' => 'The photo mime type is not allowed.',
        'video_mime_not_allowed' => 'The video mime type is not allowed.',
        'peertube_not_configured' => 'PeerTube is not configured properly.',
        'peertube_upload_failed' => 'PeerTube video upload failed.',
        'video_file_missing' => 'The selected video file is not available.',
        'invalid_snap_timestamp' => 'The snap timestamp is not valid.',
        'snap_timestamp_exceeds_duration' => 'The snap timestamp exceeds the video duration.',
        'video_status_only_for_videos' => 'Video status is available only for video media.',
        'feature_pack_not_available' => 'The selected feature pack is not available.',
        'stripe_not_configured' => 'Stripe is not configured correctly.',
        'stripe_checkout_session_failed' => 'Creating the Stripe checkout session failed.',
        'checkout_order_not_pending' => 'The checkout order is no longer pending.',
    ],
    'notifications' => [
        'snap_created_title' => 'New snap on your video',
        'snap_created_message' => ':user added a ":kind" snap at second :second on the video ":title".',
        'video_ready_title' => 'Video ready',
        'video_ready_message' => 'Your video ":title" is ready to be watched.',
        'video_failed_title' => 'Video unavailable',
        'video_failed_message' => 'There was a problem with your video ":title".',
    ],
];
