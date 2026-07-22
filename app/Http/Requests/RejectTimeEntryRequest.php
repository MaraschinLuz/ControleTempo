<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('time_entry'));
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:2000']];
    }
}
