<?php

/**
 * Messaggi auth: UI (auth_ui.php) + messaggi API v4 (prevalgono su stessa chiave).
 */
return array_merge(
    require __DIR__.'/auth_ui.php',
    [
        'invalid_credentials' => 'Credenziali non valide.',
        'logged_out' => 'Logout effettuato correttamente.',
        'too_many_attempts' => 'Troppi tentativi. Riprova tra poco.',
        'forgot_password_sent' => 'Se l\'email esiste, riceverai istruzioni per reimpostare la password.',
        'password_reset_success' => 'Password reimpostata correttamente.',
        'email_verified' => 'Email verificata correttamente.',
        'invalid_verification_link' => 'Il link di verifica non è valido o è scaduto.',
        'user_not_found' => 'Utente non trovato.',
        'email_not_verified' => 'Devi verificare la tua email prima di continuare.',
        'verification_link_sent' => 'Link di verifica inviato correttamente.',
        'invalid_account_type' => 'Il tuo account non ha i permessi per eseguire questa azione.',
        'insufficient_role' => 'Non hai i permessi necessari per eseguire questa azione.',
        'unauthenticated' => 'Utente non autenticato.',
    ],
);
