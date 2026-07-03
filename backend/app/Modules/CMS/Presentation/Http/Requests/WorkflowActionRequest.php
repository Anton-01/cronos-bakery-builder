<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared input for simple workflow transitions (submit for review, approve,
 * reject): an optional free-text comment recorded in the workflow history.
 */
class WorkflowActionRequest extends FormRequest
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
            'comment' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function commentText(): ?string
    {
        $comment = $this->validated('comment') ?? $this->validated('reason');

        return is_string($comment) && $comment !== '' ? $comment : null;
    }
}
