<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MonitorType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitorRequest extends FormRequest
{
    /**
     * Authorization is handled by the controller's policy checks.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Heartbeat monitors are created through a dedicated flow (they carry a
     * generated token and no target), so they are excluded here.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MonitorType::class)->except([MonitorType::Heartbeat])],
            'target' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535', 'required_if:type,tcp'],
            'interval_sec' => ['required', 'integer', 'between:30,86400'],
            'timeout_sec' => ['required', 'integer', 'between:1,300'],
            'failure_threshold' => ['required', 'integer', 'between:1,20'],
            'is_paused' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Add cross-field validation that a single rule cannot express.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->integer('timeout_sec') >= $this->integer('interval_sec')) {
                $validator->errors()->add(
                    'timeout_sec',
                    'Таймаут должен быть меньше интервала проверки.',
                );
            }
        });
    }
}
