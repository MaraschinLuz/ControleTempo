<?php

namespace App\Http\Controllers;

use App\Actions\CancelTimeEntryAction;
use App\Actions\StartTimeEntryAction;
use App\Actions\StopTimeEntryAction;
use App\Http\Requests\StartTimeEntryRequest;
use App\Models\Activity;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TimerController extends Controller
{
    public function store(StartTimeEntryRequest $request, StartTimeEntryAction $action): RedirectResponse
    {
        $action->execute($request->user(), Project::findOrFail($request->integer('project_id')), Activity::findOrFail($request->integer('activity_id')), $request->string('description')->toString() ?: null);

        return back()->with('success', 'Trabalho iniciado com sucesso.');
    }

    public function stop(TimeEntry $timeEntry, StopTimeEntryAction $action): RedirectResponse
    {
        Gate::authorize('controlTimer', $timeEntry);
        $action->execute($timeEntry, request()->user());

        return back()->with('success', 'Trabalho finalizado com sucesso.');
    }

    public function cancel(TimeEntry $timeEntry, CancelTimeEntryAction $action): RedirectResponse
    {
        Gate::authorize('controlTimer', $timeEntry);
        $action->execute($timeEntry, request()->user());

        return back()->with('success', 'Registro cancelado.');
    }
}
