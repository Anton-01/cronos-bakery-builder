<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Enums;

/**
 * Publication lifecycle of a page. Only Published pages are exposed to the
 * public frontend. PendingReview and Scheduled are intermediate states used
 * by the editorial workflow ({@see ContentStatus} drives the allowed
 * transitions); they exist here so the Eloquent cast accepts every status the
 * workflow can persist.
 */
enum PageStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Scheduled = 'scheduled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingReview => 'Pendiente de revisión',
            self::Published => 'Publicado',
            self::Scheduled => 'Programado',
            self::Archived => 'Archivado',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
