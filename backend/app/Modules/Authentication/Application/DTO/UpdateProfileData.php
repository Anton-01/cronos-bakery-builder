<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Application\DTO;

use App\Shared\Application\DTO\DataTransferObject;

final class UpdateProfileData extends DataTransferObject
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phone,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            phone: $data['phone'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
        ];
    }
}
