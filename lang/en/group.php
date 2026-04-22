<?php

return [
    'errors' => [
        'not_visible' => 'This group is not available.',
        'already_member' => 'You are already an active member of this group.',
        'last_owner' => 'You cannot leave or remove the last owner of the group.',
        'cannot_manage_owner' => 'You do not have permission to manage a group owner.',
        'cannot_promote_to_owner' => 'Only an owner can promote a member to owner.',
        'request_not_pending' => 'This request is no longer pending.',
        'membership_not_active' => 'The user is not an active member of the group.',
    ],
    'validation' => [
        'owner_role_required' => 'The group must always have at least one active owner.',
    ],
    'notifications' => [
        'join_requested_title' => 'New group join request',
        'join_requested_message' => ':user requested to join the group ":group".',
        'join_approved_title' => 'Group join request approved',
        'join_approved_message' => 'Your request to join the group ":group" has been approved.',
        'join_rejected_title' => 'Group join request rejected',
        'join_rejected_message' => 'Your request to join the group ":group" has been rejected.',
        'member_role_updated_title' => 'Group role updated',
        'member_role_updated_message' => 'Your role in the group ":group" is now :role.',
        'member_removed_title' => 'Removed from group',
        'member_removed_message' => 'You have been removed from the group ":group".',
        'announcement_published_title' => 'New group announcement',
        'announcement_published_message' => 'A new announcement has been published in ":group": ":title".',
    ],
    'roles' => [
        'owner' => 'owner',
        'admin' => 'admin',
        'moderator' => 'moderator',
        'member' => 'member',
    ],
    'statuses' => [
        'pending' => 'pending',
        'active' => 'active',
        'rejected' => 'rejected',
        'left' => 'left',
        'removed' => 'removed',
    ],
    'visibility' => [
        'public_open' => 'Public - open join',
        'private_approval' => 'Private - requires approval',
    ],
];
