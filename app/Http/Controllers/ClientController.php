<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Client::class);

        return view('admin.clients.index', ['items' => Client::orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        Gate::authorize('create', Client::class);

        return view('admin.clients.form', ['item' => new Client]);
    }

    public function store(ClientRequest $request)
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')->with('success', 'Cliente criado.');
    }

    public function edit(Client $client)
    {
        Gate::authorize('update', $client);

        return view('admin.clients.form', ['item' => $client]);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')->with('success', 'Cliente atualizado.');
    }

    public function destroy(Client $client)
    {
        Gate::authorize('delete', $client);
        if (TimeEntry::query()->where('status', EntryStatus::Running)->whereHas('project', fn ($query) => $query->where('client_id', $client->id))->exists()) {
            throw ValidationException::withMessages(['client' => 'Finalize ou cancele os cronômetros dos projetos deste cliente antes de excluí-lo.']);
        }

        DB::transaction(function () use ($client) {
            $client->projects()->update(['status' => 'paused']);
            $client->projects()->get()->each->delete();
            $client->update(['active' => false]);
            $client->delete();
        });

        return back()->with('success', 'Cliente e seus projetos foram excluídos com sucesso.');
    }
}
