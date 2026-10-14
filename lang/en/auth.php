<?php

return array_merge(
    require __DIR__.'/auth_ui.php',
    [
        'invalid_credentials' => 'Invalid credentials.',
        'logged_out' => 'Logged out successfully.',
        'too_many_attempts' => 'Too many attempts. Please try again later.',
        'forgot_password_sent' => 'If the email exists, you will receive instructions to reset your password.',
        'password_reset_success' => 'Password reset successfully.',
        'email_verified' => 'Email verified successfully.',
        'invalid_verification_link' => 'The verification link is invalid or has expired.',
        'user_not_found' => 'User not found.',
        'email_not_verified' => 'You must verify your email before continuing.',
        'verification_link_sent' => 'Verification link sent successfully.',
        'smtp_not_enabled' => 'The SMTP configuration is not enabled.',
        'smtp_test_subject' => 'Slam In email test',
        'smtp_test_body' => 'The Slam In SMTP configuration is working correctly.',
        'smtp_test_sent' => 'Test email sent.',
        'smtp_test_failed' => 'Delivery failed: :message',
        'invalid_account_type' => 'Your account does not have permission to perform this action.',
        'insufficient_role' => 'You do not have the required permissions to perform this action.',
        'unauthenticated' => 'Unauthenticated.',
    ],
);
