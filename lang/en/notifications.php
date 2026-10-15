<?php

return [
    'title' => 'Notifications',
    'aria_unread' => 'Notifications, :count unread',
    'mark_all_read' => 'Mark all as read',
    'loading' => 'Loading…',
    'load_error' => 'Unable to load notifications.',
    'retry' => 'Retry',
    'empty' => 'No notifications yet.',
    'unread' => 'Unread',
    'just_now' => 'Just now',
    'someone' => 'A user',
    'types' => [
        'gig_application_received' => [
            'title' => 'New application',
            'message' => ':name applied for ":title".',
        ],
        'gig_application_updated' => [
            'title' => 'Reply to your application',
            'pending' => 'Your application for ":title" is back under review.',
            'accepted' => 'Your application for ":title" has been accepted.',
            'confirmed' => 'You have been confirmed for ":title".',
            'rejected' => 'Your application for ":title" was not selected.',
            'withdrawn' => 'Your application for ":title" has been withdrawn.',
        ],
        'gig_negotiation_opened' => [
            'title' => 'Negotiation opened',
            'message' => 'The organizer opened a negotiation on your application.',
        ],
        'gig_negotiation_event_created' => [
            'title' => 'Negotiation updated',
            'message' => 'There is a new proposal in the negotiation.',
        ],
        'gig_negotiation_agreed' => [
            'title' => 'Agreement reached',
            'message' => 'The negotiation ended with an agreement.',
        ],
        'translation_job_submitted' => [
            'title' => 'Translation submitted',
            'message' => 'A translation is ready for your review.',
        ],
        'translation_job_changes_requested' => [
            'title' => 'Changes requested',
            'message' => 'Changes have been requested on your translation.',
        ],
        'translation_job_approved' => [
            'title' => 'Translation approved',
            'message' => 'Your translation has been approved.',
        ],
        'translation_comment_created' => [
            'title' => 'New translation comment',
            'message' => 'A comment was added to the translation job.',
        ],
    ],
];
