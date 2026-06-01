<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Infrastructure\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

/**
 * Password-reset notification that links to the SPA reset form, carrying the
 * broker token and the recipient email as query parameters.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $frontend = rtrim((string) Config::get('app.frontend_url'), '/');

        $url = $frontend . '/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage())
            ->subject('Reset your Cronos Bakery password')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset password', $url)
            ->line('This link will expire in ' . Config::get('auth.passwords.users.expire', 60) . ' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
