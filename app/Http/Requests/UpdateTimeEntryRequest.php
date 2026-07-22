<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('time_entry'));
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'started_at' => ['required', 'date'], 'ended_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'justification' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
