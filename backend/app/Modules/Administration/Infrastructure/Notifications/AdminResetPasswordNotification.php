<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

/**
 * Password-reset notification for administrators. Links to the admin panel's
 * reset form rather than the customer SPA.
 */
class AdminResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $frontend = rtrim((string) Config::get('app.frontend_url'), '/');

        $url = $frontend . '/admin/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage())
            ->subject('Reset your Cronos admin password')
            ->line('A password reset was requested for your administrator account.')
            ->action('Reset password', $url)
            ->line('If you did not request this, please contact your system administrator.');
    }
}
