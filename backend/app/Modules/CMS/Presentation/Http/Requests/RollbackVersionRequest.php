<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RollbackVersionRequest extends FormRequest
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
            'version_id' => [
                'required', 'integer',
                // The version must belong to the page being rolled back.
                Rule::exists('content_versions', 'id')
                    ->where('versionable_id', (int) $this->route('page')),
            ],
        ];
    }
}
