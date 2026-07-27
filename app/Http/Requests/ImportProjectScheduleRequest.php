<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProjectScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'spreadsheet' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'spreadsheet.required' => 'Selecione uma planilha para importar.',
            'spreadsheet.mimes' => 'A planilha deve estar no formato XLSX.',
            'spreadsheet.max' => 'A planilha pode ter no máximo 10 MB.',
        ];
    }
}
