<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use InvalidArgumentException;

/**
 * The requested driver_name has no strategy registered in config/payments.php.
 */
class UnsupportedDriverException extends InvalidArgumentException
{
    public static function for(string $driverName): self
    {
        return new self("Unsupported payment driver [{$driverName}].");
    }
}
