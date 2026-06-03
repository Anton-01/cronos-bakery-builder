<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

/**
 * Lightweight base for immutable Data Transfer Objects. DTOs carry validated
 * data across the boundary between the HTTP/presentation layer and the
 * application services, so domain logic never depends on request shapes.
 */
abstract class DataTransferObject
{
    /**
     * Build the DTO from an associative array (typically validated request data).
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Expose the DTO as a plain array for persistence or serialisation.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
