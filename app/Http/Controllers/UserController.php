<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', ['items' => User::orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        return view('admin.users.form', ['item' => new User]);
    }

    public function store(UserRequest $request)
    {
        User::create($request->validated());

        return redirect()->route('users.index')->with('success', 'Usuário criado.');
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('admin.users.form', ['item' => $user]);
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuário atualizado.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        if ($user->is(auth()->user())) {
            throw ValidationException::withMessages(['user' => 'Você não pode excluir a própria conta.']);
        }
        if ($user->role === UserRole::Admin && User::query()->where('role', UserRole::Admin)->where('active', true)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'Não é possível excluir o último administrador ativo.']);
        }
        if ($user->timeEntries()->where('status', EntryStatus::Running)->exists()) {
            throw ValidationException::withMessages(['user' => 'Finalize ou cancele o cronômetro deste usuário antes de excluí-lo.']);
        }
        $user->update(['active' => false]);
        $user->delete();

        return back()->with('success', 'Usuário excluído com sucesso.');
    }
}
