<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Catalog\Domain\Events\ProductCreated;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Infrastructure\Jobs\SyncProductToSearchIndex;
use App\Modules\Catalog\Infrastructure\Listeners\IndexProductOnCreation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_active_products(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/catalog/products')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_an_admin_can_create_a_product_and_an_event_is_dispatched(): void
    {
        Event::fake([ProductCreated::class]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/catalog/products', [
            'name' => 'Chocolate Dream',
            'price_amount' => 4500,
            'currency' => 'USD',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Chocolate Dream');

        $this->assertDatabaseHas('catalog_products', ['name' => 'Chocolate Dream']);
        Event::assertDispatched(ProductCreated::class);
    }

    public function test_the_creation_listener_queues_the_search_index_job(): void
    {
        Queue::fake();
        $product = Product::factory()->create();

        (new IndexProductOnCreation())->handle(new ProductCreated($product));

        Queue::assertPushed(SyncProductToSearchIndex::class);
    }

    public function test_a_customer_cannot_create_a_product(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->postJson('/api/catalog/products', [
            'name' => 'Forbidden Cake',
            'price_amount' => 1000,
        ])->assertForbidden();
    }
}
