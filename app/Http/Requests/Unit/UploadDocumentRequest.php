<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    /**
     * Aturan validasi untuk permintaan ini.
     */
    public function rules()
    {
        return [
            'document' => 'required|file|mimes:pdf|max:10240', // Validasi untuk PDF maksimal 10MB
        ];
    }

    /**
     * Pesan error khusus untuk validasi.
     */
    public function messages()
    {
        return [
            'document.required' => 'Dokumen wajib di-upload.',
            'document.file' => 'Dokumen harus berupa file.',
            'document.mimes' => 'Dokumen harus dalam format PDF.',
            'document.max' => 'Ukuran dokumen maksimal adalah 10MB.',
        ];
    }
}
