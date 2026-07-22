<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('user') ? 'update' : 'create', $this->route('user') ?? User::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('users')->ignore($this->route('user'))], 'role' => ['required', Rule::in(['collaborator', 'manager', 'admin'])], 'active' => ['required', 'boolean'], 'password' => [$this->route('user') ? 'nullable' : 'required', 'confirmed', Password::defaults()]];
    }
}
