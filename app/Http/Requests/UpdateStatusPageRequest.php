<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\StatusPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusPageRequest extends FormRequest
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
        /** @var StatusPage $statusPage */
        $statusPage = $this->route('statusPage');

        return [
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:255',
                Rule::unique(StatusPage::class)->ignore($statusPage),
            ],
            'title' => ['required', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
