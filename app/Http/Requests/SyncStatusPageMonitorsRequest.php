<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncStatusPageMonitorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Monitors must belong to the current user, so a page cannot expose
     * someone else's monitor.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'monitors' => ['present', 'array'],
            'monitors.*.id' => [
                'required',
                'integer',
                Rule::exists(Monitor::class, 'id')->where('user_id', $user->id),
            ],
            'monitors.*.display_name' => ['nullable', 'string', 'max:255'],
            'monitors.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
