<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Services;

use App\Modules\ProductBuilder\Domain\Enums\RuleAction;
use App\Modules\ProductBuilder\Domain\Models\Product;

/**
 * Evaluates conditional dependency rules to determine which options are visible
 * for a given set of selections.
 *
 * Visibility semantics for a target option:
 *  - If it has any "show" rules, it is hidden unless at least one matches.
 *  - It is hidden if any "hide" rule matches.
 *  - With no rules, it is visible.
 *
 * Resolution runs to a fixed point so that chained dependencies (A → B → C)
 * settle correctly, ignoring selections made on options that end up hidden.
 */
final class DependencyResolver
{
    /**
     * @param  array<string, array<int, string>>  $selections  option key => selected value(s)
     * @return array<int, string> visible option keys
     */
    public function visibleOptionKeys(Product $product, array $selections): array
    {
        $options = $product->options;
        $rules = $product->rules;

        // Map option id => key for translating rule targets/sources.
        $keyById = $options->pluck('key', 'id');

        // Start optimistic: every option visible.
        $visible = $options->mapWithKeys(fn ($o) => [$o->key => true])->all();

        $maxPasses = $options->count() + 1;

        for ($pass = 0; $pass <= $maxPasses; $pass++) {
            $changed = false;

            foreach ($options as $option) {
                $optionRules = $rules->where('option_id', $option->id);

                if ($optionRules->isEmpty()) {
                    continue;
                }

                $showRules = $optionRules->where('action', RuleAction::Show);
                $hideRules = $optionRules->where('action', RuleAction::Hide);

                $showSatisfied = $showRules->isEmpty();
                foreach ($showRules as $rule) {
                    if ($this->ruleMatches($rule, $keyById, $selections, $visible)) {
                        $showSatisfied = true;
                        break;
                    }
                }

                $hidden = false;
                foreach ($hideRules as $rule) {
                    if ($this->ruleMatches($rule, $keyById, $selections, $visible)) {
                        $hidden = true;
                        break;
                    }
                }

                $newVisible = $showSatisfied && ! $hidden;

                if ($visible[$option->key] !== $newVisible) {
                    $visible[$option->key] = $newVisible;
                    $changed = true;
                }
            }

            if (! $changed) {
                break;
            }
        }

        return array_keys(array_filter($visible));
    }

    /**
     * @param  \Illuminate\Support\Collection<string, string>  $keyById
     * @param  array<string, array<int, string>>  $selections
     * @param  array<string, bool>  $visible
     */
    private function ruleMatches($rule, $keyById, array $selections, array $visible): bool
    {
        $sourceKey = $keyById[$rule->depends_on_option_id] ?? null;

        // A hidden source contributes no selection.
        if ($sourceKey === null || ($visible[$sourceKey] ?? false) === false) {
            return false;
        }

        $selected = $selections[$sourceKey] ?? [];

        return $rule->operator->matches($rule->value, $selected);
    }
}
