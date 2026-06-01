<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Cronos Admin',
            'email' => 'admin@cronos.test',
        ]);

        User::factory(5)->create();
    }
}
