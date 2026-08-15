<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return ['retroactive_entry_max_days' => ['required', 'integer', 'min:0', 'max:3650'], 'maximum_running_timer_hours' => ['required', 'integer', 'min:1', 'max:168'], 'require_retroactive_approval' => ['required', 'boolean'], 'allow_collaborator_manual_entry' => ['required', 'boolean'], 'allow_collaborator_edit' => ['required', 'boolean'], 'allow_collaborator_delete' => ['required', 'boolean']];
    }
}
