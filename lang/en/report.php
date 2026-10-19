<?php

return [
    'errors' => [
        'duplicate_open' => 'You already have an open report for this content.',
        'self_report' => 'You cannot report your own content.',
    ],
    'notifications' => [
        'created_title' => 'New report received',
        'created_message' => 'A new report has been submitted for a :type.',
        'updated_title' => 'Update on your report',
        'resolved_message' => 'Your report has been reviewed and resolved.',
        'dismissed_message' => 'Your report has been reviewed and archived.',
    ],
    'types' => [
        'poem' => 'poem',
        'article' => 'article',
        'comment' => 'comment',
        'media' => 'media',
        'event' => 'event',
        'group' => 'group',
        'content' => 'content',
    ],
    'ui' => [
        'reason' => 'Reason',
        'details' => 'Details (optional)',
        'details_placeholder' => 'Add useful details',
        'submitted' => 'Report submitted.',
        'submit_error' => 'Unable to submit the report.',
        'reasons' => [
            'spam' => 'Spam',
            'abuse' => 'Abuse',
            'harassment' => 'Harassment',
            'other' => 'Other',
        ],
    ],
];
