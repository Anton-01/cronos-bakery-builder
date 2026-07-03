<?php

declare(strict_types=1);

namespace App\Shared\Domain\Concerns;

use App\Shared\Infrastructure\Observers\AuditObserver;

/**
 * Opt-in automatic audit trail. Any Eloquent model using this trait gets its
 * created / updated / deleted / restored events recorded in `model_audit_logs`
 * (attribute-level diffs, acting admin, tenant and request IP) without any
 * code in controllers or services — the observer captures the context and a
 * queued job persists the entry in the background.
 *
 * Attributes listed in `$auditExclude` (or in the model's `$hidden` array)
 * are never written to the trail.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::observe(AuditObserver::class);
    }

    /**
     * Attribute names excluded from audit diffs, in addition to `$hidden`.
     *
     * @return array<int, string>
     */
    public function auditExclude(): array
    {
        return property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['created_at', 'updated_at', 'remember_token', 'password'];
    }

    /**
     * The brand (tenant) the audit entry belongs to, when the model is
     * brand-aware. Models with a different tenancy path may override this.
     */
    public function auditBrandId(): ?int
    {
        $value = $this->getAttribute('brand_id');

        return is_numeric($value) ? (int) $value : null;
    }
}
