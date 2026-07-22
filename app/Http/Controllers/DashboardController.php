<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $ownerId = $user->isManagerOrAdmin() && $request->filled('user_id') ? $request->integer('user_id') : ($user->isManagerOrAdmin() ? null : $user->id);
        $now = CarbonImmutable::now();
        $periodStart = $request->filled('date_from') ? CarbonImmutable::parse($request->string('date_from'))->startOfDay() : $now->startOfMonth();
        $periodEnd = $request->filled('date_to') ? CarbonImmutable::parse($request->string('date_to'))->endOfDay() : $now->endOfMonth();
        $base = TimeEntry::query()->when($ownerId, fn ($q) => $q->where('user_id', $ownerId));
        $sum = fn ($start, $end) => (clone $base)->counted()->whereBetween('started_at', [$start, $end])->sum('duration_seconds');
        $monthEntries = (clone $base)->counted()->with(['project.client', 'activity'])->whereBetween('started_at', [$periodStart, $periodEnd])->get();
        $groupSeconds = fn ($key) => $monthEntries->groupBy($key)->map(fn ($rows) => round($rows->sum('duration_seconds') / 3600, 2));

        return view('dashboard', [
            'todaySeconds' => $sum($now->startOfDay(), $now->endOfDay()),
            'weekSeconds' => $sum($now->startOfWeek(), $now->endOfWeek()),
            'monthSeconds' => $monthEntries->sum('duration_seconds'),
            'pendingSeconds' => (clone $base)->where('status', EntryStatus::Pending)->sum('duration_seconds'),
            'runningEntry' => TimeEntry::with(['project.client', 'activity'])->where('user_id', $user->id)->where('status', EntryStatus::Running)->first(),
            'recentEntries' => (clone $base)->with(['user', 'project.client', 'activity'])->latest('started_at')->limit(8)->get(),
            'projectChart' => $groupSeconds('project.name'),
            'clientChart' => $groupSeconds('project.client.name'),
            'activityChart' => $groupSeconds('activity.name'),
            'estimateChart' => Project::query()->where('status', 'active')->withSum(['timeEntries as realized_seconds' => fn ($q) => $q->counted()], 'duration_seconds')->get(),
            'clients' => Client::query()->where('active', true)->orderBy('name')->get(),
            'projects' => Project::query()->where('status', 'active')->with('client')->orderBy('name')->get(),
            'activities' => Activity::query()->where('active', true)->orderBy('name')->get(),
            'users' => $user->isManagerOrAdmin() ? User::query()->where('active', true)->orderBy('name')->get() : collect(),
        ]);
    }
}
