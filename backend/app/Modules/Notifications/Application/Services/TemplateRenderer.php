<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Application\Services;

/**
 * Renders templates by substituting {{ variable }} placeholders with context
 * values. Unknown placeholders are left blank.
 */
final class TemplateRenderer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function render(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            static fn (array $m): string => (string) ($context[$m[1]] ?? ''),
            $template,
        ) ?? $template;
    }
}
