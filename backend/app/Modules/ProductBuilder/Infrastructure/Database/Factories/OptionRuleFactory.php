<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Infrastructure\Database\Factories;

use App\Modules\ProductBuilder\Domain\Enums\RuleAction;
use App\Modules\ProductBuilder\Domain\Enums\RuleOperator;
use App\Modules\ProductBuilder\Domain\Models\OptionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OptionRule>
 */
class OptionRuleFactory extends Factory
{
    protected $model = OptionRule::class;

    public function definition(): array
    {
        return [
            'operator' => RuleOperator::Equals->value,
            'value' => 'domo',
            'action' => RuleAction::Show->value,
            'position' => 0,
        ];
    }
}
