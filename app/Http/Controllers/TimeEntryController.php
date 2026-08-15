<?php

namespace App\Http\Controllers;

use App\Actions\CreateManualTimeEntryAction;
use App\Actions\UpdateTimeEntryAction;
use App\Enums\AuditAction;
use App\Enums\EntryStatus;
use App\Http\Requests\StoreManualTimeEntryRequest;
use App\Http\Requests\UpdateTimeEntryRequest;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryAuditService;
use App\Services\TimeEntryQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TimeEntryController extends Controller
{
    public function index(Request $request, TimeEntryQueryService $filters)
    {
        Gate::authorize('viewAny', TimeEntry::class);
        $entries = $filters->apply(TimeEntry::query()->with(['user', 'project.client', 'activity'])->visibleTo($request->user()), $request->query())->paginate(20)->withQueryString();

        return view('time-entries.index', array_merge(compact('entries'), $this->options($request)));
    }

    public function create(Request $request)
    {
        Gate::authorize('createManual', TimeEntry::class);

        return view('time-entries.create', $this->options($request));
    }

    public function store(StoreManualTimeEntryRequest $request, CreateManualTimeEntryAction $action)
    {
        $owner = $request->filled('user_id') ? User::findOrFail($request->integer('user_id')) : $request->user();
        $action->execute($request->user(), $owner, Project::findOrFail($request->integer('project_id')), Activity::findOrFail($request->integer('activity_id')), $request->validated());

        return redirect()->route('time-entries.index')->with('success', 'Horas adicionadas com sucesso.');
    }

    public function show(TimeEntry $timeEntry)
    {
        Gate::authorize('view', $timeEntry);

        return view('time-entries.show', ['entry' => $timeEntry->load(['user', 'project.client', 'activity', 'approver', 'audits.changedBy'])]);
    }

    public function edit(Request $request, TimeEntry $timeEntry)
    {
        Gate::authorize('update', $timeEntry);

        return view('time-entries.edit', array_merge(['entry' => $timeEntry], $this->options($request)));
    }

    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry, UpdateTimeEntryAction $action)
    {
        $action->execute($timeEntry, $request->user(), Project::findOrFail($request->integer('project_id')), Activity::findOrFail($request->integer('activity_id')), $request->validated());

        return redirect()->route('time-entries.index')->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(Request $request, TimeEntry $timeEntry, TimeEntryAuditService $audits)
    {
        Gate::authorize('delete', $timeEntry);
        abort_if($timeEntry->status === EntryStatus::Running, 422, 'Finalize ou cancele o cronômetro antes de excluir.');
        $audits->record($timeEntry, $request->user(), AuditAction::Deleted, $audits->snapshot($timeEntry), null);
        $timeEntry->delete();

        return back()->with('success', 'Registro excluído.');
    }

    private function options(Request $request): array
    {
        return [
            'clients' => Client::query()->orderBy('name')->get(),
            'projects' => Project::query()->with('client')->orderBy('name')->get(),
            'activities' => Activity::query()->orderBy('name')->get(),
            'users' => $request->user()->isManagerOrAdmin() ? User::query()->where('active', true)->orderBy('name')->get() : collect([$request->user()]),
        ];
    }
}
