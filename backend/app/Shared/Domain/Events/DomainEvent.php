<?php

declare(strict_types=1);

namespace App\Shared\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base class for domain events. Events express that something meaningful
 * happened in a bounded context; listeners (often queued) react without the
 * originating module knowing about them.
 */
abstract class DomainEvent
{
    use Dispatchable;
    use SerializesModels;

    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
