<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Models;

use App\Modules\Calendar\Infrastructure\Database\Factories\ProductionRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-product production lead time (hours). A null product_id is the global
 * default used when a product has no specific rule.
 *
 * @property int $id
 * @property int|null $product_id
 * @property int $lead_time_hours
 */
class ProductionRule extends Model
{
    use HasFactory;

    protected $table = 'calendar_production_rules';

    protected $fillable = ['product_id', 'lead_time_hours'];

    protected $casts = [
        'lead_time_hours' => 'integer',
    ];

    protected static function newFactory(): ProductionRuleFactory
    {
        return ProductionRuleFactory::new();
    }
}
