<?php

return [
    'errors' => [
        'not_visible' => 'Questo gruppo non è disponibile.',
        'already_member' => 'Sei già membro attivo di questo gruppo.',
        'last_owner' => 'Non puoi lasciare o rimuovere l\'ultimo owner del gruppo.',
        'cannot_manage_owner' => 'Non hai i permessi per gestire un owner del gruppo.',
        'cannot_promote_to_owner' => 'Solo un owner può promuovere un membro a owner.',
        'request_not_pending' => 'La richiesta non è più in stato pending.',
        'membership_not_active' => 'L\'utente non è un membro attivo del gruppo.',
    ],
    'validation' => [
        'owner_role_required' => 'Il gruppo deve avere almeno un owner attivo.',
    ],
    'notifications' => [
        'join_requested_title' => 'Nuova richiesta di ingresso al gruppo',
        'join_requested_message' => ':user ha richiesto di entrare nel gruppo ":group".',
        'join_approved_title' => 'Richiesta di ingresso approvata',
        'join_approved_message' => 'La tua richiesta di ingresso al gruppo ":group" è stata approvata.',
        'join_rejected_title' => 'Richiesta di ingresso rifiutata',
        'join_rejected_message' => 'La tua richiesta di ingresso al gruppo ":group" è stata rifiutata.',
        'member_role_updated_title' => 'Ruolo gruppo aggiornato',
        'member_role_updated_message' => 'Il tuo ruolo nel gruppo ":group" e ora :role.',
        'member_removed_title' => 'Rimosso dal gruppo',
        'member_removed_message' => 'Sei stato rimosso dal gruppo ":group".',
        'announcement_published_title' => 'Nuovo annuncio nel gruppo',
        'announcement_published_message' => 'Nel gruppo ":group" è stato pubblicato un nuovo annuncio: ":title".',
    ],
    'roles' => [
        'owner' => 'owner',
        'admin' => 'admin',
        'moderator' => 'moderator',
        'member' => 'membro',
    ],
    'statuses' => [
        'pending' => 'in attesa',
        'active' => 'attivo',
        'rejected' => 'rifiutato',
        'left' => 'uscito',
        'removed' => 'rimosso',
    ],
    'visibility' => [
        'public_open' => 'Pubblico - accesso diretto',
        'private_approval' => 'Privato - richiede approvazione',
    ],
];
