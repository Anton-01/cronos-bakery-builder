<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentRejected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Model $content,
        public readonly int $reviewerId,
        public readonly string $workflowId,
        public readonly ?string $reason = null,
    ) {}
}
