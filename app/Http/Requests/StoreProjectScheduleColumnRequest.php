<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectScheduleColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                'max:80',
                Rule::unique('project_schedule_columns', 'label')
                    ->where('project_id', $this->route('project')->id),
            ],
            'type' => ['required', Rule::in(['text', 'textarea', 'date', 'number'])],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Informe o nome da nova coluna.',
            'label.unique' => 'Já existe uma coluna com esse nome neste projeto.',
            'type.in' => 'Selecione um tipo de coluna válido.',
        ];
    }
}
