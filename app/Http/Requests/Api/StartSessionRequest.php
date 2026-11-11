<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    public function rules(): array
    {
        return [
            'map_id' => ['required', 'integer', 'exists:cer_maps,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'map_id.required' => 'ID Map materi wajib ditentukan.',
            'map_id.exists'   => 'ID Map materi tidak ditemukan.',
        ];
    }
}
