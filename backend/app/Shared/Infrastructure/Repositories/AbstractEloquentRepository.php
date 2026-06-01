<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Repositories;

use App\Shared\Domain\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base Eloquent implementation of {@see RepositoryInterface}. Concrete module
 * repositories extend this and provide their model via {@see model()}, adding
 * only the query methods specific to their aggregate.
 */
abstract class AbstractEloquentRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->resolveModel();
    }

    /**
     * Fully-qualified class name of the Eloquent model this repository manages.
     *
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    public function find(int|string $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->model->newQuery()->where($field, $value)->first();
    }

    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(int|string $id, array $attributes): Model
    {
        $record = $this->findOrFail($id);
        $record->update($attributes);

        return $record->refresh();
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    private function resolveModel(): Model
    {
        $class = $this->model();

        return new $class();
    }
}
