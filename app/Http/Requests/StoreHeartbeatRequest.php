<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeartbeatRequest extends FormRequest
{
    /**
     * Authorization is handled by the controller's policy check.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'interval_sec' => ['required', 'integer', 'between:60,86400'],
            'grace_sec' => ['required', 'integer', 'between:30,86400'],
        ];
    }
}
