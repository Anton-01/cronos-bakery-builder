<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
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
            // Strict allow-list: raster images only, 2 MB cap, real content
            // sniffing via the image rule (extension spoofing is rejected).
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
