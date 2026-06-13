<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Enums;

enum ContentStatus: string
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
            self::PendingReview => 'Pendiente de Revisión',
            self::Published => 'Publicado',
            self::Scheduled => 'Programado',
            self::Archived => 'Archivado',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingReview, self::Archived],
            self::PendingReview => [self::Draft, self::Published, self::Scheduled],
            self::Published => [self::Draft, self::Archived],
            self::Scheduled => [self::Draft, self::Published],
            self::Archived => [self::Draft],
        };
    }

    public function requiresApproval(): bool
    {
        return $this === self::Published || $this === self::Scheduled;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
