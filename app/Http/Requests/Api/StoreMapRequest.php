<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'map_id'         => ['nullable', 'integer', 'exists:cer_maps,id'],
            'title'          => ['required', 'string', 'max:100'],
            'triplets'       => ['required', 'array', 'min:1'],
            'triplets.*.id'  => ['nullable', 'integer'],
            'triplets.*.claim'     => ['nullable', 'string'],
            'triplets.*.evidence'  => ['nullable', 'string'],
            'triplets.*.reasoning' => ['nullable', 'string'],
            'allow_feedback' => ['nullable', 'boolean'],
            'allow_reading'  => ['nullable', 'boolean'],
            'reading_text'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'    => 'Judul materi wajib diisi.',
            'title.max'         => 'Judul materi maksimal 100 karakter.',
            'triplets.required' => 'Set triplet CER minimal 1 item.',
            'triplets.min'      => 'Set triplet CER minimal 1 item.',
            'map_id.exists'     => 'Materi yang ingin diperbarui tidak ditemukan.',
        ];
    }
}
