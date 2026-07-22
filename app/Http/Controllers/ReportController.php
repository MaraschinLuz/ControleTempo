<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryQueryService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, TimeEntryQueryService $filters)
    {
        $entries = $filters->apply(TimeEntry::query()->with(['user', 'project.client', 'activity'])->visibleTo($request->user()), $request->query())->get();
        $counted = $entries->filter(fn ($entry) => in_array($entry->status->value, ['approved', 'completed'], true));

        return view('reports.index', [
            'entries' => $entries,
            'totalSeconds' => $counted->sum('duration_seconds'),
            'byUser' => $counted->groupBy('user.name')->map->sum('duration_seconds'),
            'byClient' => $counted->groupBy('project.client.name')->map->sum('duration_seconds'),
            'byProject' => $counted->groupBy('project.name')->map->sum('duration_seconds'),
            'byActivity' => $counted->groupBy('activity.name')->map->sum('duration_seconds'),
            'estimateRows' => Project::query()->withSum(['timeEntries as realized_seconds' => fn ($q) => $q->counted()], 'duration_seconds')->orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(), 'projects' => Project::orderBy('name')->get(),
            'activities' => Activity::orderBy('name')->get(),
            'users' => $request->user()->isManagerOrAdmin() ? User::orderBy('name')->get() : collect([$request->user()]),
        ]);
    }
}
