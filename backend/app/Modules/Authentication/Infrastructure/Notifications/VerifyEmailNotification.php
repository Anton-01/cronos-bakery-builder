<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Infrastructure\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Email-verification notification tailored for the SPA: the signed backend
 * verification URL is wrapped in a frontend URL so the user lands back in the
 * application after confirming.
 */
class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $signedUrl = $this->verificationUrl($notifiable);

        return (new MailMessage())
            ->subject('Verify your Cronos Bakery account')
            ->line('Thanks for signing up! Please confirm your email address to activate your account.')
            ->action('Verify email', $signedUrl)
            ->line('If you did not create an account, no further action is required.');
    }

    protected function verificationUrl($notifiable): string
    {
        $apiUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $frontend = rtrim((string) Config::get('app.frontend_url'), '/');

        return $frontend . '/verify-email?' . http_build_query(['verify_url' => $apiUrl]);
    }
}
