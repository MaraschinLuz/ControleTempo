<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('project') ? 'update' : 'create', $this->route('project') ?? Project::class);
    }

    public function rules(): array
    {
        return ['client_id' => ['required', 'exists:clients,id'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'], 'status' => ['required', Rule::in(['planned', 'active', 'paused', 'completed'])], 'estimated_hours' => ['nullable', 'numeric', 'min:0'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date']];
    }
}
