<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Services;

use App\Modules\CMS\Application\DTO\SectionData;
use App\Modules\CMS\Domain\Models\Section;
use Illuminate\Support\Collection;

/**
 * CRUD for the reusable section library.
 */
final class SectionService
{
    public function all(): Collection
    {
        return Section::query()->orderBy('name')->get();
    }

    public function get(int $id): Section
    {
        return Section::query()->findOrFail($id);
    }

    public function create(SectionData $data): Section
    {
        return Section::create($data->toAttributes());
    }

    public function update(int $id, SectionData $data): Section
    {
        $section = $this->get($id);
        $section->update($data->toAttributes());

        return $section->refresh();
    }

    public function delete(int $id): void
    {
        $this->get($id)->delete();
    }
}
