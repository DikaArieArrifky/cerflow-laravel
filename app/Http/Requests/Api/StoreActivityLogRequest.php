<?php

namespace App\Http\Requests\Api;

use App\Services\ActivityLogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Support both REST standard (action_type / action_data) and webCerflow existing (type / data)
        if ($this->has('type') && ! $this->has('action_type')) {
            $this->merge(['action_type' => $this->input('type')]);
        }
        if ($this->has('data') && ! $this->has('action_data')) {
            $this->merge(['action_data' => $this->input('data')]);
        }
    }

    public function rules(): array
    {
        return [
            'session_id'  => ['required', 'integer', 'exists:user_sessions,id'],
            'action_type' => ['required', 'string', Rule::in(ActivityLogService::ALLOWED_ACTIONS)],
            'action_data' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required'  => 'Session ID wajib disertakan.',
            'session_id.exists'    => 'Session ID tidak valid atau tidak ditemukan.',
            'action_type.required' => 'Tipe aksi (action_type) wajib diisi.',
            'action_type.in'       => 'Tipe aksi tidak didukung.',
        ];
    }
}
