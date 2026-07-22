<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\UserRole;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->role === UserRole::Admin && User::query()->where('role', UserRole::Admin)->where('active', true)->count() <= 1) {
            throw ValidationException::withMessages(['password' => 'Não é possível excluir o último administrador ativo.'])->errorBag('userDeletion');
        }
        if ($user->timeEntries()->where('status', EntryStatus::Running)->exists()) {
            throw ValidationException::withMessages(['password' => 'Finalize ou cancele seu cronômetro antes de excluir a conta.'])->errorBag('userDeletion');
        }

        Auth::logout();

        $user->update(['active' => false]);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
