<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract every repository must honour. Application services depend on this
 * abstraction rather than on concrete Eloquent implementations, keeping the
 * domain persistence-agnostic.
 */
interface RepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15);

    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    public function findBy(string $field, mixed $value): ?Model;

    public function create(array $attributes): Model;

    public function update(int|string $id, array $attributes): Model;

    public function delete(int|string $id): bool;
}
