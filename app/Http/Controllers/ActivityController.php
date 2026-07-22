<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Activity::class);

        return view('admin.activities.index', ['items' => Activity::orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        Gate::authorize('create', Activity::class);

        return view('admin.activities.form', ['item' => new Activity]);
    }

    public function store(ActivityRequest $request)
    {
        Activity::create($request->validated());

        return redirect()->route('activities.index')->with('success', 'Atividade criada.');
    }

    public function edit(Activity $activity)
    {
        Gate::authorize('update', $activity);

        return view('admin.activities.form', ['item' => $activity]);
    }

    public function update(ActivityRequest $request, Activity $activity)
    {
        $activity->update($request->validated());

        return redirect()->route('activities.index')->with('success', 'Atividade atualizada.');
    }

    public function destroy(Activity $activity)
    {
        Gate::authorize('delete', $activity);
        if ($activity->timeEntries()->where('status', EntryStatus::Running)->exists()) {
            throw ValidationException::withMessages(['activity' => 'Finalize ou cancele os cronômetros desta atividade antes de excluí-la.']);
        }
        $activity->update(['active' => false]);
        $activity->delete();

        return back()->with('success', 'Atividade excluída com sucesso.');
    }
}
