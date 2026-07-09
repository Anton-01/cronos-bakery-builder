<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use App\Modules\ProductBuilder\Domain\Enums\RuleAction;
use App\Modules\ProductBuilder\Domain\Enums\RuleOperator;
use App\Modules\ProductBuilder\Infrastructure\Database\Factories\OptionRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A conditional dependency rule. Example: when option "Forma" equals "Domo",
 * show option "Perlas".
 *
 * @property int $id
 * @property int $product_id
 * @property int $option_id           Target option whose visibility changes.
 * @property int $depends_on_option_id Source option that triggers the rule.
 * @property RuleOperator $operator
 * @property string $value
 * @property RuleAction $action
 * @property int $position
 */
class OptionRule extends Model
{
    use HasFactory;

    protected $table = 'pb_option_rules';

    protected $fillable = [
        'product_id',
        'option_id',
        'depends_on_option_id',
        'operator',
        'value',
        'action',
        'position',
    ];

    protected $casts = [
        'operator' => RuleOperator::class,
        'action' => RuleAction::class,
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function newFactory(): OptionRuleFactory
    {
        return OptionRuleFactory::new();
    }
}
