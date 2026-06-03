<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

/**
 * Base type for business-rule violations. Modules throw subclasses of this to
 * signal that an operation is not allowed by the domain, distinct from
 * infrastructure or framework failures.
 */
class DomainException extends RuntimeException
{
}
