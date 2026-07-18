<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\StatusPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStatusPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'alpha_dash', 'max:255', Rule::unique(StatusPage::class)],
            'title' => ['required', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
