<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Policies;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Catalog\Domain\Models\Product;

/**
 * Authorization rules for the Product aggregate. Reads are public; writes are
 * restricted to administrators.
 */
class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Product $product): bool
    {
        return $product->is_active || ($user?->isAdmin() ?? false);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
