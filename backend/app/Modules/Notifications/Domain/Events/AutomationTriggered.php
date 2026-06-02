<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A generic, decoupled automation trigger. Owning modules raise this with an
 * event key and context; the Notifications module resolves the template and
 * dispatches the message asynchronously.
 */
final class AutomationTriggered
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $event,
        public readonly array $context,
        public readonly string $recipient,
        public readonly ?string $dedupeKey = null,
    ) {
    }
}
