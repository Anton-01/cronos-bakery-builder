<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * At-rest encryption for the `credentials` JSONB column.
 *
 * Each VALUE is encrypted individually (Crypt / AES-256-CBC with APP_KEY)
 * while the KEYS remain plaintext, so the column stays a valid, inspectable
 * JSONB document ({"secret_key": "eyJpdiI6..."}), but no secret is ever
 * readable without the application key. Encryption/decryption is fully
 * transparent to the model consumer.
 *
 * @implements CastsAttributes<array<string, string|null>|null, array<string, mixed>|null>
 */
class EncryptedCredentials implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string|null>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stored = json_decode((string) $value, true);
        if (! is_array($stored)) {
            return null;
        }

        $credentials = [];
        foreach ($stored as $field => $ciphertext) {
            try {
                $credentials[$field] = $ciphertext === null
                    ? null
                    : Crypt::decryptString((string) $ciphertext);
            } catch (DecryptException) {
                // Undecryptable value (e.g. APP_KEY rotated without re-encrypting):
                // surface the field as unset instead of breaking every read.
                $credentials[$field] = null;
            }
        }

        return $credentials;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            $value = (array) $value;
        }

        $encrypted = [];
        foreach ($value as $field => $plain) {
            $encrypted[$field] = ($plain === null || $plain === '')
                ? null
                : Crypt::encryptString((string) $plain);
        }

        return json_encode($encrypted, JSON_THROW_ON_ERROR);
    }
}
