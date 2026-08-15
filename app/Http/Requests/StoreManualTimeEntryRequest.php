<?php

namespace App\Http\Requests;

use App\Models\TimeEntry;
use Illuminate\Foundation\Http\FormRequest;

class StoreManualTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('createManual', TimeEntry::class);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'started_at' => ['required', 'date'], 'ended_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'justification' => ['required', 'string', 'max:2000'],
        ];
    }
}
