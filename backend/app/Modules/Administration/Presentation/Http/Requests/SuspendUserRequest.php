<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'suspended_until' => ['nullable', 'date', 'after:now'],
        ];
    }
}
