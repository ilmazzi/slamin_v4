<?php

namespace Domain\Notification\Enums;

enum NotificationCategory: string
{
    case ChatMessages = 'chat_messages';
    case EventParticipantInvites = 'event_participant_invites';
    case EventAudienceInvites = 'event_audience_invites';
    case GigApplications = 'gig_applications';
    case Comments = 'comments';
    case Likes = 'likes';
    case Snaps = 'snaps';
    case EventUpdates = 'event_updates';
    case EventInviteResponses = 'event_invite_responses';
    case GigUpdates = 'gig_updates';
    case Translations = 'translations';
    case Moderation = 'moderation';
    case Groups = 'groups';
    case Reports = 'reports';
    case MediaProcessing = 'media_processing';
    case WeeklyDigest = 'weekly_digest';

    public static function fromType(string $type): ?self
    {
        return match (true) {
            $type === 'event_participant_invited' => self::EventParticipantInvites,
            $type === 'event_audience_invited' => self::EventAudienceInvites,
            $type === 'gig.application_received' => self::GigApplications,
            $type === 'comment_replied', str_ends_with($type, '_commented') => self::Comments,
            str_ends_with($type, '_liked') => self::Likes,
            $type === 'media_snap_created' => self::Snaps,
            $type === 'event_updated', $type === 'event_availability_finalized' => self::EventUpdates,
            str_starts_with($type, 'event_') && str_ends_with($type, '_invite_response') => self::EventInviteResponses,
            str_starts_with($type, 'gig.') => self::GigUpdates,
            str_starts_with($type, 'translation_') => self::Translations,
            (bool) preg_match('/^(poem|article|event)_(approved|rejected)$/', $type) => self::Moderation,
            str_starts_with($type, 'group_') => self::Groups,
            str_starts_with($type, 'report_') => self::Reports,
            str_starts_with($type, 'media_video_') => self::MediaProcessing,
            default => null,
        };
    }

    /**
     * Likes are batched into a daily summary and chat messages are mailed on a delay,
     * so neither is sent at notification time.
     */
    public function isMailedImmediately(): bool
    {
        return ! in_array($this, [self::Likes, self::ChatMessages, self::WeeklyDigest], true);
    }
}
