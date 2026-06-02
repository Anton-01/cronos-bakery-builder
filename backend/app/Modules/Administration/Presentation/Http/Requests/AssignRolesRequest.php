<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Requests;

use App\Modules\Administration\Domain\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRolesRequest extends FormRequest
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
            'roles' => ['present', 'array'],
            'roles.*' => [Rule::in(AdminRole::values())],
        ];
    }
}
