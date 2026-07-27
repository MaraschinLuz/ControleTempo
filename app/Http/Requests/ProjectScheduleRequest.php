<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['sometimes', 'array', 'max:300'],
            'rows.*' => ['array'],
            'rows.*.column_1' => ['nullable', 'string', 'max:100'],
            'rows.*.column_2' => ['nullable', 'string', 'max:100'],
            'rows.*.demand' => ['nullable', 'string', 'max:5000'],
            'rows.*.ai_suggestion' => ['nullable', 'string', 'max:5000'],
            'rows.*.completion_status' => ['nullable', Rule::in(['Sim', 'Não', 'Em andamento', 'Agendado'])],
            'rows.*.execution_date' => ['nullable', 'date'],
            'rows.*.responsible' => ['nullable', 'string', 'max:255'],
            'rows.*.client_responsible' => ['nullable', 'string', 'max:255'],
            'rows.*.client_contact' => ['nullable', 'string', 'max:255'],
            'rows.*.scope' => ['nullable', 'string', 'max:5000'],
            'rows.*.completed_demands' => ['nullable', 'string', 'max:5000'],
            'rows.*.remaining_work' => ['nullable', 'string', 'max:5000'],
            'rows.*.completion_date' => ['nullable', 'date'],
            'rows.*.hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.max' => 'O cronograma pode ter no máximo 300 linhas.',
            'rows.*.completion_status.in' => 'Selecione um status válido.',
            'rows.*.hours.numeric' => 'A quantidade de horas deve ser numérica.',
            'rows.*.hours.min' => 'A quantidade de horas não pode ser negativa.',
        ];
    }
}
