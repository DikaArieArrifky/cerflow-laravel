<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:user_sessions,id'],
            'map_id'     => ['required', 'integer', 'exists:cer_maps,id'],
            'score'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'map_data'   => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID wajib disertakan.',
            'session_id.exists'   => 'Session ID tidak ditemukan.',
            'map_id.required'     => 'Map ID wajib disertakan.',
            'map_id.exists'       => 'Map ID tidak ditemukan.',
            'score.numeric'       => 'Format skor harus berupa angka.',
            'score.min'           => 'Skor minimal adalah 0.',
            'score.max'           => 'Skor maksimal adalah 100.',
        ];
    }
}
