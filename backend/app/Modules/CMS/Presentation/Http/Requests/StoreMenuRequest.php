<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use App\Modules\CMS\Domain\Enums\MenuLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', Rule::enum(MenuLocation::class)],
            'is_active' => ['boolean'],
        ];
    }
}
