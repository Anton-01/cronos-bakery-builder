<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Application\Services;

use App\Modules\Authentication\Domain\Enums\Role;
use App\Modules\Authentication\Domain\Models\SocialAccount;
use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

/**
 * Orchestrates social login (Google, Facebook, Apple). Resolves an existing
 * linked account, links a provider to a matching email, or provisions a brand
 * new verified customer — then issues an API token.
 */
final class SocialAuthService
{
    private const PROVIDERS = ['google', 'facebook', 'apple'];

    public function redirectUrl(string $provider): string
    {
        $this->assertSupported($provider);

        return Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
    }

    /**
     * @return array{user: User, token: string}
     */
    public function authenticate(string $provider): array
    {
        $this->assertSupported($provider);

        /** @var SocialiteUser $socialUser */
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = $this->resolveUser($provider, $socialUser);
        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    private function resolveUser(string $provider, SocialiteUser $socialUser): User
    {
        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($account !== null) {
            return $account->user;
        }

        $user = User::query()->where('email', $socialUser->getEmail())->first()
            ?? $this->createUserFromSocial($socialUser);

        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        return $user;
    }

    private function createUserFromSocial(SocialiteUser $socialUser): User
    {
        [$firstName, $lastName] = $this->splitName($socialUser->getName());

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $socialUser->getEmail(),
            'password' => null,
            'avatar' => $socialUser->getAvatar(),
            'role' => Role::Customer->value,
        ]);

        // Email ownership is already proven by the social provider.
        $user->markEmailAsVerified();

        return $user;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['Customer', ''];
        }

        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    private function assertSupported(string $provider): void
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw ValidationException::withMessages([
                'provider' => ["Unsupported social provider [{$provider}]."],
            ]);
        }
    }
}
