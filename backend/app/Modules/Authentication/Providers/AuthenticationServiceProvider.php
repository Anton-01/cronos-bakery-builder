<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Providers;

use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AuthenticationServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return dirname(__DIR__);
    }

    protected function listeners(): array
    {
        return [
            // Send the verification email when a customer registers.
            Registered::class => [SendEmailVerificationNotification::class],

            // Register the Apple driver with Socialite (Google & Facebook ship
            // with Socialite core).
            SocialiteWasCalled::class => [AppleExtendSocialite::class . '@handle'],
        ];
    }
}
