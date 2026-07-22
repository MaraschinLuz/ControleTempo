<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('activity') ? 'update' : 'create', $this->route('activity') ?? Activity::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255', Rule::unique('activities')->ignore($this->route('activity'))], 'description' => ['nullable', 'string', 'max:2000'], 'active' => ['required', 'boolean']];
    }
}
