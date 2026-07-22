<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Http\Requests\ProjectRequest;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Project::class);

        return view('admin.projects.index', ['items' => Project::with('client')->orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        Gate::authorize('create', Project::class);

        return view('admin.projects.form', ['item' => new Project, 'clients' => Client::orderBy('name')->get()]);
    }

    public function store(ProjectRequest $request)
    {
        Project::create($request->validated());

        return redirect()->route('projects.index')->with('success', 'Projeto criado.');
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return view('admin.projects.form', ['item' => $project, 'clients' => Client::orderBy('name')->get()]);
    }

    public function update(ProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return redirect()->route('projects.index')->with('success', 'Projeto atualizado.');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);
        if ($project->timeEntries()->where('status', EntryStatus::Running)->exists()) {
            throw ValidationException::withMessages(['project' => 'Finalize ou cancele os cronômetros deste projeto antes de excluí-lo.']);
        }
        $project->update(['status' => 'paused']);
        $project->delete();

        return back()->with('success', 'Projeto excluído com sucesso.');
    }
}
