<?php

namespace App\Http\Requests;

use App\Enums\DemandStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('demand'));
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(DemandStatus::class)]];
    }
}
