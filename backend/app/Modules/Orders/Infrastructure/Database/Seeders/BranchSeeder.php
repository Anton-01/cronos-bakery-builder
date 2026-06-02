<?php

declare(strict_types=1);

namespace App\Modules\Orders\Infrastructure\Database\Seeders;

use App\Modules\Orders\Domain\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * Seeds the pickup branches (sucursales).
 */
class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['Cronos Centro', 'Av. Central 100, San José'],
            ['Cronos Escazú', 'Plaza Tempo, Escazú'],
            ['Cronos Heredia', 'Av. 7, Heredia'],
        ];

        foreach ($branches as $position => [$name, $address]) {
            Branch::factory()->create([
                'name' => $name,
                'address' => $address,
                'position' => $position,
            ]);
        }
    }
}
