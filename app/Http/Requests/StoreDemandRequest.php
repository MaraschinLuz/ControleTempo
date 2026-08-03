<?php

namespace App\Http\Requests;

use App\Enums\DemandPriority;
use App\Enums\DemandStatus;
use App\Models\Demand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetUserId = $this->integer('user_id') ?: $this->user()->id;

        return $this->user()->can('create', Demand::class)
            && ($this->user()->isManagerOrAdmin() || $targetUserId === $this->user()->id);
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('active', true)->whereNull('deleted_at')),
            ],
            'project_id' => [
                'required',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(DemandStatus::class)],
            'priority' => ['required', Rule::enum(DemandPriority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Selecione o responsável pela demanda.',
            'user_id.exists' => 'O responsável selecionado não está disponível.',
            'project_id.required' => 'Selecione o projeto da demanda.',
            'project_id.exists' => 'O projeto selecionado não está disponível.',
            'title.required' => 'Informe o título da demanda.',
            'title.max' => 'O título pode ter no máximo 255 caracteres.',
            'description.max' => 'A descrição pode ter no máximo 5.000 caracteres.',
            'status.required' => 'Selecione o status da demanda.',
            'status.enum' => 'O status selecionado é inválido.',
            'priority.required' => 'Selecione a prioridade da demanda.',
            'priority.enum' => 'A prioridade selecionada é inválida.',
            'due_date.date' => 'Informe um prazo válido.',
        ];
    }
}
