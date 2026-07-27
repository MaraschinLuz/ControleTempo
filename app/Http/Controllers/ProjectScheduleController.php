<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectScheduleRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProjectScheduleController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            ->with('client')
            ->orderBy('name')
            ->get();

        $selectedProject = $request->filled('project_id')
            ? Project::query()->with('client')->findOrFail($request->integer('project_id'))
            : null;

        $scheduleRows = $selectedProject
            ? $selectedProject->scheduleRows()
                ->orderBy('position')
                ->get()
                ->map(fn ($row) => [
                    ...Arr::only($row->toArray(), [
                        'column_1',
                        'column_2',
                        'demand',
                        'ai_suggestion',
                        'completion_status',
                        'responsible',
                        'client_responsible',
                        'client_contact',
                        'scope',
                        'completed_demands',
                        'remaining_work',
                        'hours',
                    ]),
                    'execution_date' => $row->execution_date?->format('Y-m-d'),
                    'completion_date' => $row->completion_date?->format('Y-m-d'),
                ])
                ->values()
            : collect();

        return view('project-schedules.index', compact('projects', 'selectedProject', 'scheduleRows'));
    }

    public function update(ProjectScheduleRequest $request, Project $project)
    {
        $rows = $request->validated('rows', []);

        DB::transaction(function () use ($project, $rows) {
            $project->scheduleRows()->delete();

            foreach (array_values($rows) as $index => $row) {
                $project->scheduleRows()->create([
                    ...$row,
                    'position' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('project-schedules.index', ['project_id' => $project->id])
            ->with('success', 'Cronograma salvo com sucesso.');
    }
}
