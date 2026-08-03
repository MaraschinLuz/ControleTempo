<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDemandRequest;
use App\Http\Requests\UpdateDemandRequest;
use App\Http\Requests\UpdateDemandStatusRequest;
use App\Models\Demand;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DemandController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Demand::class);

        $users = $request->user()->isManagerOrAdmin()
            ? User::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
            : collect([$request->user()]);

        $selectedUser = $request->user();

        if ($request->user()->isManagerOrAdmin() && $request->filled('user_id')) {
            $selectedUser = User::query()->where('active', true)->findOrFail($request->integer('user_id'));
        }

        $demands = Demand::query()
            ->with(['project.client'])
            ->whereBelongsTo($selectedUser)
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')->trim()).'%';

                $query->where(fn ($query) => $query
                    ->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search));
            })
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->get();

        $projects = Project::query()->with('client')->orderBy('name')->get();

        return view('demands.index', compact('demands', 'projects', 'selectedUser', 'users'));
    }

    public function store(StoreDemandRequest $request)
    {
        $data = $request->validated();
        $data['position'] = $this->nextPosition($data['user_id'], $data['status']);
        $demand = Demand::create($data);

        return redirect()
            ->route('demands.index', ['user_id' => $demand->user_id])
            ->with('success', 'Demanda adicionada ao quadro.');
    }

    public function update(UpdateDemandRequest $request, Demand $demand)
    {
        $data = $request->validated();

        if ($demand->status->value !== $data['status']) {
            $data['position'] = $this->nextPosition($demand->user_id, $data['status']);
        }

        $demand->update($data);

        return redirect()
            ->route('demands.index', ['user_id' => $demand->user_id])
            ->with('success', 'Demanda atualizada.');
    }

    public function updateStatus(UpdateDemandStatusRequest $request, Demand $demand)
    {
        $status = $request->validated('status');

        if ($demand->status->value !== $status) {
            $demand->update([
                'status' => $status,
                'position' => $this->nextPosition($demand->user_id, $status),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Status atualizado.', 'status' => $status]);
        }

        return back()->with('success', 'Status da demanda atualizado.');
    }

    public function destroy(Demand $demand)
    {
        Gate::authorize('delete', $demand);
        $demand->delete();

        return back()->with('success', 'Demanda excluída.');
    }

    private function nextPosition(int $userId, string $status): int
    {
        return (int) Demand::query()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->max('position') + 1;
    }
}
