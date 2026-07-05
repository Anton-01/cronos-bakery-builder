<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Requests;

use App\Modules\Authentication\Domain\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['sometimes', Rule::enum(Role::class)],
        ];
    }
}
