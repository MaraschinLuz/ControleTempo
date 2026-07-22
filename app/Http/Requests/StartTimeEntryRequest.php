<?php

namespace App\Http\Requests;

use App\Models\TimeEntry;
use Illuminate\Foundation\Http\FormRequest;

class StartTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TimeEntry::class);
    }

    public function rules(): array
    {
        return ['project_id' => ['required', 'integer', 'exists:projects,id'], 'activity_id' => ['required', 'integer', 'exists:activities,id'], 'description' => ['nullable', 'string', 'max:2000']];
    }
}
